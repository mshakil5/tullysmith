<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
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
        $mode      = $request->input('mode', 'weekly');
        $offset    = (int) $request->input('offset', 0);
        $workerId  = $request->input('worker_id');
        $jobId     = $request->input('job_id');

        [$start, $end, $label] = $this->range($mode, $offset);

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

        // Graph data — group by date
        $byDate = $logs->groupBy(fn($l) => $l->clock_in_at->toDateString());
        $graphLabels = [];
        $graphHours  = [];
        $graphSessions = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $graphLabels[]   = $current->format($mode === 'monthly' ? 'M d' : ($mode === 'daily' ? 'H:i' : 'D d'));
            $graphHours[]    = round($byDate->get($dateStr, collect())->sum('total_hours'), 2);
            $graphSessions[] = $byDate->get($dateStr, collect())->count();
            $current->addDay();
        }

        // Per-worker breakdown
        $workerBreakdown = $logs->groupBy('worker_id')->map(function ($wLogs) {
            $worker = $wLogs->first()->worker;
            return [
                'name'     => $worker->name ?? '—',
                'hours'    => round($wLogs->sum('total_hours'), 2),
                'sessions' => $wLogs->count(),
                'jobs'     => $wLogs->pluck('service_job_id')->unique()->count(),
            ];
        })->values();

        // Per-job breakdown
        $jobBreakdown = $logs->groupBy('service_job_id')->map(function ($jLogs) {
            $job = $jLogs->first()->job;
            return [
                'title'    => $job->job_title ?? '—',
                'job_id'   => $job->job_id    ?? '—',
                'hours'    => round($jLogs->sum('total_hours'), 2),
                'sessions' => $jLogs->count(),
                'workers'  => $jLogs->pluck('worker_id')->unique()->count(),
            ];
        })->values();

        return response()->json([
            'label'           => $label,
            'start'           => $start->format('d M Y'),
            'end'             => $end->format('d M Y'),
            'total_hours'     => $totalHours,
            'total_sessions'  => $totalSessions,
            'unique_workers'  => $uniqueWorkers,
            'unique_jobs'     => $uniqueJobs,
            'graph_labels'    => $graphLabels,
            'graph_hours'     => $graphHours,
            'graph_sessions'  => $graphSessions,
            'worker_breakdown'=> $workerBreakdown,
            'job_breakdown'   => $jobBreakdown,
        ]);
    }

    public function export(Request $request)
    {
        $mode     = $request->input('mode', 'weekly');
        $offset   = (int) $request->input('offset', 0);
        $workerId = $request->input('worker_id');
        $jobId    = $request->input('job_id');

        [$start, $end, $label] = $this->range($mode, $offset);

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