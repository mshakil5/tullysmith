<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\ServiceJob;
use App\Models\ServiceJobChecklist;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->hasRole('Worker')) abort(403);
            return $next($request);
        });
    }

    public function index()
    {
        $workers = User::byRole('Worker')->select('id', 'name')->orderBy('name')->get();
        $jobs    = ServiceJob::select('id', 'job_title', 'job_id')->orderByDesc('id')->get();
        return view('admin.reports.index', compact('workers', 'jobs'));
    }

    public function data(Request $request)
    {
        $mode     = $request->input('mode', 'weekly');
        $offset   = (int) $request->input('offset', 0);
        $workerId = $request->input('worker_id');
        $jobId    = $request->input('job_id');
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        if ($fromDate && $toDate) {
            $start = Carbon::parse($fromDate)->startOfDay();
            $end   = Carbon::parse($toDate)->endOfDay();
            $label = Carbon::parse($fromDate)->format('d M Y') . ' — ' . Carbon::parse($toDate)->format('d M Y');
            $mode  = 'custom';
        } else {
            [$start, $end, $label] = $this->range($mode, $offset);
        }

        $query = TimeLog::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('clock_in_at', [$start, $end])
            ->whereNotNull('clock_out_at');

        if ($workerId) $query->where('worker_id', $workerId);
        if ($jobId)    $query->where('service_job_id', $jobId);

        $logs = $query->orderBy('clock_in_at')->get();

        $totalHours    = round($logs->sum('total_hours'), 2);
        $totalSessions = $logs->count();
        $uniqueWorkers = $logs->pluck('worker_id')->unique()->count();
        $uniqueJobs    = $logs->pluck('service_job_id')->unique()->count();

        $byDate      = $logs->groupBy(fn($l) => $l->clock_in_at->toDateString());
        $graphLabels = [];
        $graphHours  = [];
        $graphSessions = [];

        if ($mode !== 'custom') {
            $current = $start->copy();
            while ($current->lte($end)) {
                $dateStr         = $current->toDateString();
                $graphLabels[]   = $current->format($mode === 'monthly' ? 'M d' : ($mode === 'daily' ? 'H:i' : 'D d'));
                $graphHours[]    = round($byDate->get($dateStr, collect())->sum('total_hours'), 2);
                $graphSessions[] = $byDate->get($dateStr, collect())->count();
                $current->addDay();
            }
        } else {
            $current = $start->copy();
            while ($current->lte($end)) {
                $dateStr         = $current->toDateString();
                $graphLabels[]   = $current->format('d M');
                $graphHours[]    = round($byDate->get($dateStr, collect())->sum('total_hours'), 2);
                $graphSessions[] = $byDate->get($dateStr, collect())->count();
                $current->addDay();
            }
        }

        $workerBreakdown = $logs->groupBy('worker_id')->map(function ($wLogs) {
            $worker = $wLogs->first()->worker;
            return [
                'name'     => $worker->name ?? '—',
                'hours'    => round($wLogs->sum('total_hours'), 2),
                'sessions' => $wLogs->count(),
                'jobs'     => $wLogs->pluck('service_job_id')->unique()->count(),
            ];
        })->values();

        $jobBreakdown = $logs->groupBy('service_job_id')->map(function ($jLogs) use ($start, $end) {
            $job = $jLogs->first()->job;

            $expenses = Document::whereIn('type', ['invoice', 'receipt'])
                ->where('service_job_id', $jLogs->first()->service_job_id)
                ->where('status', 'approved')
                ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
                ->get(['type', 'title', 'amount', 'invoice_date', 'file']);

            $checklists = ServiceJobChecklist::where('service_job_id', $jLogs->first()->service_job_id)
                ->where('status', 'approved')
                ->with(['checklist:id,title', 'answers.item:id,question,type', 'answers.answeredBy:id,name'])
                ->get();

            return [
                'title'       => $job->job_title ?? '—',
                'job_id'      => $job->job_id    ?? '—',
                'hours'       => round($jLogs->sum('total_hours'), 2),
                'sessions'    => $jLogs->count(),
                'workers'     => $jLogs->pluck('worker_id')->unique()->count(),
                'expenses'    => $expenses->map(fn($e) => [
                    'type'         => ucfirst($e->type),
                    'title'        => $e->title ?? '—',
                    'amount'       => number_format($e->amount, 2),
                    'invoice_date' => $e->invoice_date ? Carbon::parse($e->invoice_date)->format('d M Y') : '—',
                    'file'         => asset($e->file),
                ])->values(),
                'expense_total' => number_format($expenses->sum('amount'), 2),
                'checklists'  => $checklists->map(fn($c) => [
                    'title'    => $c->checklist->title ?? '—',
                    'show_at'  => $c->show_at,
                    'answered' => $c->answers->count(),
                    'answers'  => $c->answers->map(fn($a) => [
                        'question'    => $a->item->question ?? '—',
                        'type'        => $a->item->type     ?? '—',
                        'answer'      => $a->answer,
                        'photo'       => $a->photo_path ? asset($a->photo_path) : null,
                        'answered_by' => $a->answeredBy->name ?? '—',
                        'answered_at' => $a->updated_at->format('d M Y, h:i A'),
                    ])->values(),
                ])->values(),
            ];
        })->values();

        return response()->json([
            'label'            => $label,
            'start'            => $start->format('d M Y'),
            'end'              => $end->format('d M Y'),
            'start_date'       => $start->format('d M Y'),
            'end_date'         => $end->format('d M Y'),
            'total_hours'      => $totalHours,
            'total_sessions'   => $totalSessions,
            'unique_workers'   => $uniqueWorkers,
            'unique_jobs'      => $uniqueJobs,
            'graph_labels'     => $graphLabels,
            'graph_hours'      => $graphHours,
            'graph_sessions'   => $graphSessions,
            'worker_breakdown' => $workerBreakdown,
            'job_breakdown'    => $jobBreakdown,
        ]);
    }

    public function export(Request $request)
    {
        $mode     = $request->input('mode', 'weekly');
        $offset   = (int) $request->input('offset', 0);
        $workerId = $request->input('worker_id');
        $jobId    = $request->input('job_id');
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        if ($fromDate && $toDate) {
            $start = Carbon::parse($fromDate)->startOfDay();
            $end   = Carbon::parse($toDate)->endOfDay();
            $label = $fromDate . '_to_' . $toDate;
        } else {
            [$start, $end, $label] = $this->range($mode, $offset);
        }

        $query = TimeLog::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('clock_in_at', [$start, $end])
            ->whereNotNull('clock_out_at')
            ->orderBy('clock_in_at');

        if ($workerId) $query->where('worker_id', $workerId);
        if ($jobId)    $query->where('service_job_id', $jobId);

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
            'Content-Disposition' => 'attachment; filename="report_' . $label . '.csv"',
        ]);
    }

    private function range(string $mode, int $offset): array
    {
        return match ($mode) {
            'daily'   => [now()->addDays($offset)->startOfDay(),     now()->addDays($offset)->endOfDay(),     now()->addDays($offset)->format('D, M d Y')],
            'monthly' => [now()->addMonths($offset)->startOfMonth(), now()->addMonths($offset)->endOfMonth(), now()->addMonths($offset)->format('F Y')],
            default   => [
                now()->addWeeks($offset)->startOfWeek(),
                now()->addWeeks($offset)->endOfWeek(),
                now()->addWeeks($offset)->startOfWeek()->format('M d') . ' - ' . now()->addWeeks($offset)->endOfWeek()->format('M d, Y'),
            ],
        };
    }
}