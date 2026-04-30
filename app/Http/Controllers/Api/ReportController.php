<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistAnswer;
use App\Models\CompanyDetails;
use App\Models\Document;
use App\Models\ServiceJob;
use App\Models\TimeLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function resolveRange(Request $request): array
    {
        $from   = $request->input('from_date');
        $to     = $request->input('to_date');
        $mode   = $request->input('mode', 'weekly');
        $offset = (int) $request->input('offset', 0);

        if ($from && $to) {
            return [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
                Carbon::parse($from)->format('d M Y') . ' — ' . Carbon::parse($to)->format('d M Y'),
                'custom',
            ];
        }

        return match ($mode) {
            'daily'   => [
                now()->addDays($offset)->startOfDay(),
                now()->addDays($offset)->endOfDay(),
                now()->addDays($offset)->format('D, d M Y'),
                'daily',
            ],
            'monthly' => [
                now()->addMonths($offset)->startOfMonth(),
                now()->addMonths($offset)->endOfMonth(),
                now()->addMonths($offset)->format('F Y'),
                'monthly',
            ],
            'all' => [
                Carbon::createFromDate(2000, 1, 1)->startOfDay(),
                now()->endOfDay(),
                'All Time',
                'all',
            ],
            default => [
                now()->addWeeks($offset)->startOfWeek(),
                now()->addWeeks($offset)->endOfWeek(),
                now()->addWeeks($offset)->startOfWeek()->format('d M')
                    . ' – ' . now()->addWeeks($offset)->endOfWeek()->format('d M Y'),
                'weekly',
            ],
        };
    }

    private function numberToWords(float $amount): string
    {
        $pounds = (int) floor($amount);
        $pence  = (int) round(($amount - $pounds) * 100);

        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                 'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                 'Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

        $convert = function (int $n) use ($ones, $tens, &$convert): string {
            if ($n === 0) return '';
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
            return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $convert($n % 100) : '');
        };

        $convertLarge = function (int $n) use ($convert): string {
            if ($n === 0) return 'Zero';
            $result = '';
            if ($n >= 1000000) { $result .= $convert((int)($n / 1000000)) . ' Million '; $n %= 1000000; }
            if ($n >= 1000)    { $result .= $convert((int)($n / 1000))    . ' Thousand '; $n %= 1000; }
            $result .= $convert($n);
            return trim($result);
        };

        $words = $convertLarge($pounds) . ' Pound' . ($pounds !== 1 ? 's' : '');
        if ($pence > 0) $words .= ' and ' . $convertLarge($pence) . ' Pence';
        return $words . ' Only';
    }

    public function filterOptions()
    {
        $workers = User::byRole('Worker')
            ->where('status', 1)
            ->select('id', 'name')
            ->latest()
            ->get()
            ->map(fn($w) => ['id' => $w->id, 'name' => $w->name]);

        $jobs = ServiceJob::select('id', 'job_title', 'job_id')
            ->orderByDesc('id')
            ->get()
            ->map(fn($j) => ['id' => $j->id, 'job_title' => $j->job_title, 'job_id' => $j->job_id]);

        return response()->json(compact('workers', 'jobs'));
    }

    public function timePdf(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $workerFiltered = (bool) $request->worker_id;
        $jobFiltered    = (bool) $request->job_id;

        $query = TimeLog::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('clock_in_at', [$start, $end])
            ->whereNotNull('clock_out_at');

        if ($request->worker_id) $query->where('worker_id', $request->worker_id);
        if ($request->job_id)    $query->where('service_job_id', $request->job_id);

        $logs    = $query->orderBy('clock_in_at')->get();
        $company = CompanyDetails::firstOrCreate();

        $workerLabel = $workerFiltered
            ? (User::find($request->worker_id)?->name ?? 'All Workers')
            : 'All Workers';
        $jobLabel = $jobFiltered
            ? (ServiceJob::find($request->job_id)?->job_title ?? 'All Jobs')
            : 'All Jobs';

        $data = [
            'company'        => $company,
            'label'          => $label,
            'worker'         => $workerLabel,
            'job'            => $jobLabel,
            'generated_at'   => now()->format('d M Y, h:i A'),
            'total_hours'    => round($logs->sum('total_hours'), 2),
            'workerFiltered' => $workerFiltered,
            'jobFiltered'    => $jobFiltered,
            'logs'           => $logs->map(fn($l) => [
                'date'      => $l->clock_in_at->format('d M Y'),
                'worker'    => $l->worker->name   ?? '—',
                'job'       => $l->job->job_title ?? '—',
                'job_id'    => $l->job->job_id    ?? '—',
                'clock_in'  => $l->clock_in_at->format('h:i A'),
                'clock_out' => $l->clock_out_at ? $l->clock_out_at->format('h:i A') : 'Active',
                'hours'     => number_format($l->total_hours, 2),
            ])->values(),
        ];

        $filename = 'time_report_' . now()->format('d-M-Y') . '.pdf';
        return Pdf::loadView('api.reports.time_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    public function expensePdf(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $jobFiltered = (bool) $request->job_id;

        $query = Document::with('job:id,job_title,job_id', 'user:id,name')
            ->whereIn('type', ['invoice', 'receipt'])
            ->where('status', 'approved')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);

        if ($request->job_id) $query->where('service_job_id', $request->job_id);

        $expenses = $query->orderBy('invoice_date')->get();
        $company  = CompanyDetails::firstOrCreate();

        $jobLabel = $jobFiltered
            ? (ServiceJob::find($request->job_id)?->job_title ?? 'All Jobs')
            : 'All Jobs';

        $totalAmount = $expenses->sum('amount');

        $data = [
            'company'      => $company,
            'label'        => $label,
            'job'          => $jobLabel,
            'generated_at' => now()->format('d M Y, h:i A'),
            'total_amount' => number_format($totalAmount, 2),
            'total_words'  => $this->numberToWords($totalAmount),
            'jobFiltered'  => $jobFiltered,
            'expenses'     => $expenses->map(fn($e) => [
                'date'   => $e->invoice_date
                    ? Carbon::parse($e->invoice_date)->format('d M Y') : '—',
                'job'    => $e->job->job_title ?? '—',
                'job_id' => $e->job->job_id    ?? '—',
                'title'  => $e->title          ?? '—',
                'amount' => number_format($e->amount, 2),
            ])->values(),
        ];

        $filename = 'expense_report_' . now()->format('d-M-Y') . '.pdf';
        return Pdf::loadView('api.reports.expense_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    public function checklistPdf(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $workerFiltered = (bool) $request->worker_id;
        $jobFiltered    = (bool) $request->job_id;

        $query = ChecklistAnswer::with([
            'serviceJobChecklist.serviceJob:id,job_title,job_id',
            'serviceJobChecklist.checklist:id,title',
            'item:id,question,type',
            'answeredBy:id,name',
        ])
        ->whereBetween('updated_at', [$start, $end])
        ->whereHas('serviceJobChecklist', fn($q) => $q->where('status', 'approved'));

        if ($request->job_id) {
            $query->whereHas('serviceJobChecklist',
                fn($q) => $q->where('service_job_id', $request->job_id));
        }
        if ($request->worker_id) {
            $query->where('answered_by', $request->worker_id);
        }

        $answers = $query->orderBy('updated_at')->get();
        $company = CompanyDetails::firstOrCreate();

        $workerLabel = $workerFiltered
            ? (User::find($request->worker_id)?->name ?? 'All Workers')
            : 'All Workers';
        $jobLabel = $jobFiltered
            ? (ServiceJob::find($request->job_id)?->job_title ?? 'All Jobs')
            : 'All Jobs';

        $data = [
            'company'        => $company,
            'label'          => $label,
            'worker'         => $workerLabel,
            'job'            => $jobLabel,
            'generated_at'   => now()->format('d M Y, h:i A'),
            'total'          => $answers->count(),
            'workerFiltered' => $workerFiltered,
            'jobFiltered'    => $jobFiltered,
            'answers'        => $answers->map(fn($a) => [
                'date'      => $a->updated_at->format('d M Y'),
                'time'      => $a->updated_at->format('h:i A'),
                'worker'    => $a->answeredBy->name ?? '—',
                'job'       => $a->serviceJobChecklist->serviceJob->job_title ?? '—',
                'job_id'    => $a->serviceJobChecklist->serviceJob->job_id    ?? '—',
                'checklist' => $a->serviceJobChecklist->checklist->title       ?? '—',
                'show_at_raw' => $a->serviceJobChecklist->show_at ?? 'both',
                'question'  => $a->item->question ?? '—',
                'type'      => $a->item->type     ?? 'text',
                'answer'    => $a->answer,
            ])->values(),
        ];

        $filename = 'checklist_report_' . now()->format('d-M-Y') . '.pdf';
        return Pdf::loadView('api.reports.checklist_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }
}