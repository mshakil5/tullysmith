<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistAnswer;
use App\Models\CompanyDetails;
use App\Models\Document;
use App\Models\ServiceJob;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->hasRole('Worker')) abort(403);
            return $next($request);
        });
    }

    private function getFilters()
    {
        $workers = User::byRole('Worker')->where('status', 1)->select('id', 'name', 'status')->latest()->get();
        $jobs    = ServiceJob::select('id', 'job_title', 'job_id')->orderByDesc('id')->get();
        return compact('workers', 'jobs');
    }

    private function resolveRange(Request $request): array
    {
        $from = $request->input('from_date');
        $to   = $request->input('to_date');
        $mode = $request->input('mode', 'weekly');
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
                now()->addWeeks($offset)->startOfWeek()->format('d M') . ' – ' . now()->addWeeks($offset)->endOfWeek()->format('d M Y'),
                'weekly',
            ],
        };
    }

    // ── Time Report ──────────────────────────────────────────────
    public function timeIndex()
    {
        return view('admin.reports.time', $this->getFilters());
    }

    public function timeData(Request $request)
    {
        [$start, $end, $label, $mode] = $this->resolveRange($request);

        $query = TimeLog::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('clock_in_at', [$start, $end])
            ->whereNotNull('clock_out_at');

        if ($request->worker_id) $query->where('worker_id', $request->worker_id);
        if ($request->job_id)    $query->where('service_job_id', $request->job_id);

        $logs = $query->orderBy('clock_in_at')->get();

        $totalHours    = round($logs->sum('total_hours'), 2);
        $totalSessions = $logs->count();
        $uniqueWorkers = $logs->pluck('worker_id')->unique()->count();
        $uniqueJobs    = $logs->pluck('service_job_id')->unique()->count();

        // Graph data
        $byDate        = $logs->groupBy(fn($l) => $l->clock_in_at->toDateString());
        $graphLabels   = [];
        $graphHours    = [];
        $graphSessions = [];

        if (!in_array($mode, ['all'])) {
            $current = $start->copy();
            $fmt = match ($mode) {
                'daily'   => 'H:i',
                'monthly' => 'd M',
                default   => 'D d',
            };
            while ($current->lte($end)) {
                $dateStr         = $current->toDateString();
                $graphLabels[]   = $current->format($fmt);
                $graphHours[]    = round($byDate->get($dateStr, collect())->sum('total_hours'), 2);
                $graphSessions[] = $byDate->get($dateStr, collect())->count();
                $current->addDay();
            }
        }

        // By worker breakdown
        $workerBreakdown = $logs->groupBy('worker_id')->map(function ($wLogs) {
            $worker = $wLogs->first()->worker;
            $byJob  = $wLogs->groupBy('service_job_id')->map(fn($jl) => [
                'job_title' => $jl->first()->job->job_title ?? '—',
                'job_id'    => $jl->first()->job->job_id    ?? '—',
                'hours'     => round($jl->sum('total_hours'), 2),
                'sessions'  => $jl->count(),
            ])->values();

            $dailyBreakdown = $wLogs->groupBy(fn($l) => $l->clock_in_at->toDateString())->map(function ($dl, $date) {
                return [
                    'date'     => Carbon::parse($date)->format('D, d M Y'),
                    'hours'    => round($dl->sum('total_hours'), 2),
                    'sessions' => $dl->count(),
                    'entries'  => $dl->map(fn($l) => [
                        'job'       => $l->job->job_title ?? '—',
                        'clock_in'  => $l->clock_in_at->format('h:i A'),
                        'clock_out' => $l->clock_out_at ? $l->clock_out_at->format('h:i A') : 'Active',
                        'hours'     => number_format($l->total_hours, 2),
                    ])->values(),
                ];
            })->values();

            return [
                'name'            => $worker->name ?? '—',
                'hours'           => round($wLogs->sum('total_hours'), 2),
                'sessions'        => $wLogs->count(),
                'jobs'            => $wLogs->pluck('service_job_id')->unique()->count(),
                'job_breakdown'   => $byJob,
                'daily_breakdown' => $dailyBreakdown,
            ];
        })->values();

        // By job breakdown
        $jobBreakdown = $logs->groupBy('service_job_id')->map(function ($jLogs) {
            $job = $jLogs->first()->job;
            $dailyBreakdown = $jLogs->groupBy(fn($l) => $l->clock_in_at->toDateString())->map(function ($dl, $date) {
                return [
                    'date'     => Carbon::parse($date)->format('D, d M Y'),
                    'hours'    => round($dl->sum('total_hours'), 2),
                    'sessions' => $dl->count(),
                    'entries'  => $dl->map(fn($l) => [
                        'worker'    => $l->worker->name ?? '—',
                        'clock_in'  => $l->clock_in_at->format('h:i A'),
                        'clock_out' => $l->clock_out_at ? $l->clock_out_at->format('h:i A') : 'Active',
                        'hours'     => number_format($l->total_hours, 2),
                    ])->values(),
                ];
            })->values();

            return [
                'title'           => $job->job_title ?? '—',
                'job_id'          => $job->job_id    ?? '—',
                'hours'           => round($jLogs->sum('total_hours'), 2),
                'sessions'        => $jLogs->count(),
                'workers'         => $jLogs->pluck('worker_id')->unique()->count(),
                'daily_breakdown' => $dailyBreakdown,
            ];
        })->values();

        return response()->json([
            'label'          => $label,

            'total_hours'    => $totalHours,
            'total_sessions' => $totalSessions,
            'unique_workers' => $uniqueWorkers,
            'unique_jobs'    => $uniqueJobs,

            'graph_labels'   => $graphLabels,
            'graph_hours'    => $graphHours,
            'graph_sessions' => $graphSessions,

            'logs' => $logs->map(fn($l) => [
                'date'      => $l->clock_in_at->format('D, d M Y'),
                'worker'    => $l->worker->name ?? '—',
                'job'       => $l->job->job_title ?? '—',
                'job_id'    => $l->job->job_id ?? '—',
                'clock_in'  => $l->clock_in_at->format('h:i A'),
                'clock_out' => $l->clock_out_at ? $l->clock_out_at->format('h:i A') : 'Active',
                'hours'     => number_format($l->total_hours, 2),
            ])->values(),
        ]);
    }

    public function timeExport(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $query = TimeLog::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('clock_in_at', [$start, $end])
            ->whereNotNull('clock_out_at')
            ->orderBy('clock_in_at');

        if ($request->worker_id) $query->where('worker_id', $request->worker_id);
        if ($request->job_id)    $query->where('service_job_id', $request->job_id);

        $logs = $query->get();

        $csv = "Date,Worker,Job,Job ID,Clock In,Clock Out,Hours\n";
        foreach ($logs as $log) {
            $jobTitle = str_replace('"', '""', $log->job->job_title ?? '');
            $csv .= implode(',', [
                $log->clock_in_at->format('d/m/Y'),
                '"' . ($log->worker->name ?? '') . '"',
                '"' . $jobTitle . '"',
                $log->job->job_id ?? '',
                $log->clock_in_at->format('h:i A'),
                $log->clock_out_at->format('h:i A'),
                number_format($log->total_hours, 2),
            ]) . "\n";
        }
        $csv .= "\nTotal Hours,,,,,,  " . number_format($logs->sum('total_hours'), 2) . "h\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="time_report_' . $label . '.csv"',
        ]);
    }

    public function expenseIndex()
    {
        return view('admin.reports.expense', $this->getFilters());
    }

    public function expenseData(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $query = Document::with('job:id,job_title,job_id', 'job.client:id,name', 'user:id,name')
            ->whereIn('type', ['invoice', 'receipt'])
            ->where('status', 'approved')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()]);

        if ($request->job_id)    $query->where('service_job_id', $request->job_id);
        if ($request->worker_id) $query->where('created_by', $request->worker_id);

        $expenses = $query->orderBy('invoice_date')->get();

        $totalAmount    = $expenses->sum('amount');
        $totalCount     = $expenses->count();
        $uniqueJobs     = $expenses->pluck('service_job_id')->unique()->count();
        $uniqueWorkers  = $expenses->pluck('created_by')->unique()->count();

        return response()->json([
            'label'          => $label,
            'total_amount'   => number_format($totalAmount, 2),
            'total_words'    => $this->numberToWords($totalAmount),
            'total_count'    => $totalCount,
            'unique_jobs'    => $uniqueJobs,
            'unique_workers' => $uniqueWorkers,

            'expenses' => $expenses->map(fn($e) => [
                'date'     => $e->invoice_date ? Carbon::parse($e->invoice_date)->format('D, d M Y') : '—',
                'worker'   => $e->user->name ?? '—',
                'job'      => $e->job->job_id ?? '—',
                'title'    => $e->title ?? '—',
                'amount'   => number_format($e->amount, 2),
                'file'     => $e->file ? asset($e->file) : null,
                'file_ext' => $e->file ? strtolower(pathinfo($e->file, PATHINFO_EXTENSION)) : null,
            ])->values(),
        ]);
    }

    public function expenseExport(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $query = Document::with('job:id,job_title,job_id', 'user:id,name')
            ->whereIn('type', ['invoice', 'receipt'])
            ->where('status', 'approved')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('invoice_date');

        if ($request->job_id)    $query->where('service_job_id', $request->job_id);
        if ($request->worker_id) $query->where('created_by', $request->worker_id);

        $expenses = $query->get();

        $csv = "Date,Worker,Job,Job ID,Title,Amount (£)\n";
        foreach ($expenses as $e) {
            $title    = str_replace('"', '""', $e->title ?? '');
            $jobTitle = str_replace('"', '""', $e->job->job_title ?? '');
            $csv .= implode(',', [
                $e->invoice_date ? Carbon::parse($e->invoice_date)->format('d/m/Y') : '',
                '"' . ($e->user->name ?? '') . '"',
                '"' . $jobTitle . '"',
                $e->job->job_id ?? '',
                '"' . $title . '"',
                number_format($e->amount, 2),
            ]) . "\n";
        }
        $csv .= "\nTotal,,,,,£" . number_format($expenses->sum('amount'), 2) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="expense_report_' . $label . '.csv"',
        ]);
    }

    // ── Checklist Report ─────────────────────────────────────────
    public function checklistIndex()
    {
        return view('admin.reports.checklist', $this->getFilters());
    }

    public function checklistData(Request $request)
    {
        [$start, $end, $label] = $this->resolveRange($request);

        $query = ChecklistAnswer::with([
            'serviceJobChecklist.serviceJob:id,job_title,job_id',
            'serviceJobChecklist.checklist:id,title',
            'item:id,question,type',
            'answeredBy:id,name'
        ])
        ->whereBetween('updated_at', [$start, $end])
        ->whereHas('serviceJobChecklist', function ($q) {
            $q->where('status', 'approved');
        });

        if ($request->job_id) {
            $query->whereHas('serviceJobChecklist', function($q) use ($request) {
                $q->where('service_job_id', $request->job_id);
            });
        }

        if ($request->worker_id) {
            $query->where('answered_by', $request->worker_id);
        }

        $answers = $query->orderBy('updated_at')->get();

        $totalAnswers   = $answers->count();
        $uniqueWorkers  = $answers->pluck('answered_by')->unique()->count();
        $uniqueJobs     = $answers->pluck('serviceJobChecklist.service_job_id')->unique()->count();
        $uniqueLists    = $answers->pluck('service_job_checklist_id')->unique()->count();

        return response()->json([
            'label'             => $label,
            'total_answers'     => $totalAnswers,
            'unique_workers'    => $uniqueWorkers,
            'unique_jobs'       => $uniqueJobs,
            'unique_checklists' => $uniqueLists,

            'answers' => $answers->map(fn($a) => [
                'date'      => $a->updated_at->format('D, d M Y'),
                'time'      => $a->updated_at->format('h:i A'),
                'worker'    => $a->answeredBy->name ?? '—',

                'job'       => $a->serviceJobChecklist->serviceJob->job_title ?? '—',
                'job_id'    => $a->serviceJobChecklist->serviceJob->job_id ?? '—',

                'checklist' => $a->serviceJobChecklist->checklist->title ?? '—',
                'show_at'   => $a->serviceJobChecklist->show_at ?? 'both',

                'question'  => $a->item->question ?? '—',
                'type'      => $a->item->type ?? 'text',

                'answer'    => $a->answer,
                'photo'     => $a->photo_path ? asset($a->photo_path) : null,
            ])->values(),
        ]);
    }
    
    private function numberToWords(float $amount): string
    {
        $pounds = (int) floor($amount);
        $pence  = (int) round(($amount - $pounds) * 100);

        $ones  = [
            '',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen'
        ];
        $tens  = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function (int $n) use ($ones, $tens, &$convert): string {
            if ($n === 0) return '';
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
            return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $convert($n % 100) : '');
        };

        $convertLarge = function (int $n) use ($convert): string {
            if ($n === 0) return 'Zero';
            $result = '';
            if ($n >= 1000000) {
                $result .= $convert((int)($n / 1000000)) . ' Million ';
                $n %= 1000000;
            }
            if ($n >= 1000) {
                $result .= $convert((int)($n / 1000))    . ' Thousand ';
                $n %= 1000;
            }
            $result .= $convert($n);
            return trim($result);
        };

        $words = $convertLarge($pounds) . ' Pound' . ($pounds !== 1 ? 's' : '');
        if ($pence > 0) {
            $words .= ' and ' . $convertLarge($pence) . ' Pence';
        }
        return $words . ' Only';
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

        return Pdf::loadView('api.reports.time_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream('time_report_' . now()->format('d-M-Y') . '.pdf');
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

        $expenses    = $query->orderBy('invoice_date')->get();
        $company     = CompanyDetails::firstOrCreate();
        $totalAmount = $expenses->sum('amount');

        $data = [
            'company'      => $company,
            'label'        => $label,
            'job'          => $jobFiltered
                ? (ServiceJob::find($request->job_id)?->job_title ?? 'All Jobs')
                : 'All Jobs',
            'generated_at' => now()->format('d M Y, h:i A'),
            'total_amount' => number_format($totalAmount, 2),
            'total_words'  => $this->numberToWords($totalAmount),
            'jobFiltered'  => $jobFiltered,
            'expenses'     => $expenses->map(function ($e) {
                $fileBase64 = null;
                $fileExt    = null;

                if ($e->file) {
                    $filePath = public_path($e->file);
                    $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $imgExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (in_array($ext, $imgExts) && file_exists($filePath)) {
                        $mime        = match ($ext) {
                            'png'  => 'image/png',
                            'gif'  => 'image/gif',
                            'webp' => 'image/webp',
                            default => 'image/jpeg',
                        };
                        $fileBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($filePath));
                        $fileExt    = $ext;
                    } elseif ($ext === 'pdf') {
                        $fileExt = 'pdf';
                    }
                }

                return [
                    'date'        => $e->invoice_date ? Carbon::parse($e->invoice_date)->format('d M Y') : '—',
                    'job'         => $e->job->job_title ?? '—',
                    'job_id'      => $e->job->job_id    ?? '—',
                    'title'       => $e->title          ?? '—',
                    'amount'      => number_format($e->amount, 2),
                    'file_base64' => $fileBase64,
                    'file_ext'    => $fileExt,
                ];
            })->values(),
        ];

        return Pdf::loadView('api.reports.expense_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->stream('expense_report_' . now()->format('d-M-Y') . '.pdf');
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

        $data = [
            'company'        => $company,
            'label'          => $label,
            'worker'         => $workerFiltered
                ? (User::find($request->worker_id)?->name ?? 'All Workers')
                : 'All Workers',
            'job'            => $jobFiltered
                ? (ServiceJob::find($request->job_id)?->job_title ?? 'All Jobs')
                : 'All Jobs',
            'generated_at'   => now()->format('d M Y, h:i A'),
            'total'          => $answers->count(),
            'workerFiltered' => $workerFiltered,
            'jobFiltered'    => $jobFiltered,
            'answers'        => $answers->map(fn($a) => [
                'date'        => $a->updated_at->format('d M Y'),
                'time'        => $a->updated_at->format('h:i A'),
                'worker'      => $a->answeredBy->name ?? '—',
                'job'         => $a->serviceJobChecklist->serviceJob->job_title ?? '—',
                'job_id'      => $a->serviceJobChecklist->serviceJob->job_id    ?? '—',
                'checklist'   => $a->serviceJobChecklist->checklist->title       ?? '—',
                'show_at_raw' => $a->serviceJobChecklist->show_at ?? 'both',
                'question'    => $a->item->question ?? '—',
                'type'        => $a->item->type     ?? 'text',
                'answer'      => $a->answer,
            ])->values(),
        ];

        return Pdf::loadView('api.reports.checklist_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->stream('checklist_report_' . now()->format('d-M-Y') . '.pdf');
    }
}