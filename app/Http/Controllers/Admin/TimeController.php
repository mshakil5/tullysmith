<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;

class TimeController extends Controller
{
    public function index()
    {
        $workerId = auth()->id();
        $today    = now()->toDateString();

        $todayAssignments = JobAssignment::with('job:id,job_title,job_id,postcode,address_line1,address_line2,city,status')
            ->where('worker_id', $workerId)
            ->where('assigned_date', $today)
            ->get();

        $activeLog = TimeLog::with('job:id,job_title,job_id')
            ->where('worker_id', $workerId)
            ->whereNull('clock_out_at')
            ->latest()->first();

        $recentLogs = TimeLog::with('job:id,job_title,job_id')
            ->where('worker_id', $workerId)
            ->latest()->take(10)->get();

        $todayHours = TimeLog::where('worker_id', $workerId)->whereDate('clock_in_at', $today)->whereNotNull('clock_out_at')->sum('total_hours');
        $weekHours  = TimeLog::where('worker_id', $workerId)->whereBetween('clock_in_at', [now()->startOfWeek(), now()->endOfWeek()])->whereNotNull('clock_out_at')->sum('total_hours');
        $monthHours = TimeLog::where('worker_id', $workerId)->whereMonth('clock_in_at', now()->month)->whereNotNull('clock_out_at')->sum('total_hours');

        return view('admin.time.index', compact('todayAssignments','activeLog','recentLogs','todayHours','weekHours','monthHours'));
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'job_assignment_id' => 'required|exists:job_assignments,id',
            'photo'             => 'required|string',
            'lat'               => 'nullable|numeric',
            'lng'               => 'nullable|numeric',
            'force'             => 'nullable|boolean', // user confirmed duplicate warning
        ]);

        $workerId = auth()->id();
        $today    = now()->toDateString();

        // Already clocked in right now
        if (TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->exists()) {
            return response()->json(['message' => 'You already have an active clock-in. Please clock out first.'], 422);
        }

        $assignment = JobAssignment::with('job:id,job_title,job_id,postcode')->findOrFail($request->job_assignment_id);
        if ($assignment->assigned_date !== $today) return response()->json(['message' => 'This job is not assigned for today.'], 422);
        if ($assignment->worker_id !== $workerId)  return response()->json(['message' => 'Unauthorized.'], 403);

        // Duplicate check — same job already completed today
        if (!$request->force) {
            $alreadyDone = TimeLog::where('worker_id', $workerId)
                ->where('job_assignment_id', $assignment->id)
                ->whereNotNull('clock_out_at')
                ->whereDate('clock_in_at', $today)
                ->exists();

            if ($alreadyDone) {
                return response()->json([
                    'warning' => true,
                    'message' => 'You have already completed a shift for this job today. Do you want to clock in again?',
                ], 200);
            }
        }

        // Best-effort location check
        $locationMsg = null;
        if ($request->filled('lat') && $request->filled('lng') && $assignment->job->postcode) {
            try {
                $geo = Http::timeout(5)->get('https://api.postcodes.io/postcodes/' . urlencode($assignment->job->postcode))->json();
                if (($geo['status'] ?? null) === 200) {
                    $dist        = $this->haversine($request->lat, $request->lng, $geo['result']['latitude'], $geo['result']['longitude']);
                    $locationMsg = $dist <= 100 ? 'location_verified' : round($dist) . 'm from job site';
                }
            } catch (\Exception $e) {
                $locationMsg = 'location_check_failed';
            }
        }

        $log = TimeLog::create([
            'worker_id'         => $workerId,
            'service_job_id'    => $assignment->service_job_id,
            'job_assignment_id' => $assignment->id,
            'clock_in_at'       => now(),
            'clock_in_photo'    => $this->savePhoto($request->photo, 'clockin', $workerId),
            'clock_in_lat'      => $request->lat,
            'clock_in_lng'      => $request->lng,
            'location_note'     => $locationMsg,
        ]);

        $msg = 'Clocked in successfully.';
        if ($locationMsg === 'location_verified') $msg .= ' Location verified ✓';
        elseif ($locationMsg && $locationMsg !== 'location_check_failed') $msg .= ' Note: ' . $locationMsg . '.';

        return response()->json([
            'message'    => $msg,
            'card_html'  => view('admin.time.partials.active-card', ['log'  => $log->load('job')])->render(),
            'stats_html' => view('admin.time.partials.stats',       $this->statsData($workerId))->render(),
            'entry_html' => view('admin.time.partials.entry',       ['log'  => $log->load('job')])->render(),
            'log_id'     => $log->id,
        ]);
    }

    public function clockOut(Request $request)
    {
        $request->validate(['photo' => 'required|string']);

        $workerId = auth()->id();
        $log = TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->latest()->firstOrFail();

        $clockOut   = now();
        $totalHours = round($log->clock_in_at->diffInMinutes($clockOut) / 60, 2);

        $log->update([
            'clock_out_at'    => $clockOut,
            'clock_out_photo' => $this->savePhoto($request->photo, 'clockout', $workerId),
            'total_hours'     => $totalHours,
        ]);

        return response()->json([
            'message'    => 'Clocked out. Total: ' . number_format($totalHours, 1) . 'h',
            'card_html'  => view('admin.time.partials.start-card',   ['todayAssignments' => $this->todayAssignments($workerId)])->render(),
            'stats_html' => view('admin.time.partials.stats',        $this->statsData($workerId))->render(),
            'entry_html' => view('admin.time.partials.entry',        ['log' => $log->fresh()->load('job')])->render(),
            'log_id'     => $log->id,
        ]);
    }

    public function stats()
    {
        return response()->json($this->statsData(auth()->id()));
    }

    public function timesheet(Request $request)
    {
        $currentUser = auth()->user();
        if ($currentUser->hasRole('Worker')) {
            $workerId = $currentUser->id;
            $selectedWorker = null;
        } else {
            $workerId = $request->query('worker_id', null);

            if (!$workerId) {
                $selectedWorker = null;
            } else {
                $selectedWorker = User::byRole('Worker')->find($workerId);
                if (!$selectedWorker) {
                    abort(404, 'Worker not found');
                }
            }
        }

        $mode   = $request->input('mode', 'weekly');
        $offset = (int) $request->input('offset', 0);

        [$start, $end, $label] = $this->timesheetRange($mode, $offset);

        $logs = collect();
        $totalHours = 0.00;
        $breakdown  = collect();

        if ($workerId) {
            $logs = TimeLog::with('job:id,job_title')
                ->where('worker_id', $workerId)
                ->whereBetween('clock_in_at', [$start, $end])
                ->orderBy('clock_in_at')
                ->get();

            $totalHours = $logs->whereNotNull('clock_out_at')->sum('total_hours');
            $breakdown  = $logs->groupBy(fn($l) => $l->clock_in_at->toDateString());
        }

        $workers = $currentUser->hasRole('Worker')
            ? collect()
            : User::byRole('Worker')->select('id', 'name')->orderBy('name')->get();

        return view('admin.time.timesheet', compact(
            'logs', 'totalHours', 'breakdown', 'mode', 'offset', 'label', 'start', 'end',
            'workerId', 'selectedWorker', 'workers', 'currentUser'
        ));
    }

    public function exportTimesheet(Request $request)
    {
        $workerId = auth()->id();
        $mode     = $request->input('mode', 'weekly');
        $offset   = (int) $request->input('offset', 0);

        [$start, $end, $label] = $this->timesheetRange($mode, $offset);

        $logs       = TimeLog::with('job:id,job_title')
                        ->where('worker_id', $workerId)
                        ->whereBetween('clock_in_at', [$start, $end])
                        ->orderBy('clock_in_at')
                        ->get();

        $totalHours = $logs->whereNotNull('clock_out_at')->sum('total_hours');

        $csv = "Date,Job,Clock In,Clock Out,Hours\n";

        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->clock_in_at->format('d/m/Y'),
                '"' . ($log->job->job_title ?? '') . '"',
                $log->clock_in_at->format('h:i A'),
                $log->clock_out_at ? $log->clock_out_at->format('h:i A') : 'Active',
                $log->total_hours ?? '',
            ]) . "\n";
        }

        $csv .= "\nTotal,,,, " . number_format($totalHours, 2) . "h\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="timesheet_' . str_replace([' ', '-'], '_', $label) . '.csv"',
        ]);
    }

    private function savePhoto(string $base64, string $prefix, int $workerId): ?string
    {
        if (!$base64) return null;
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $file = $prefix . '_' . $workerId . '_' . mt_rand(10000000, 99999999) . '.webp';
        $path = public_path('uploads/time-logs/');
        if (!file_exists($path)) mkdir($path, 0755, true);
        Image::make($data)->encode('webp', 60)->save($path . $file);
        return '/uploads/time-logs/' . $file;
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000;
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function todayAssignments(int $workerId)
    {
        return JobAssignment::with('job:id,job_title,job_id,postcode,address_line1,address_line2,city,status')
            ->where('worker_id', $workerId)->where('assigned_date', now()->toDateString())->get();
    }

    private function statsData(int $workerId): array
    {
        $today = now()->toDateString();
        return [
            'todayHours' => round(TimeLog::where('worker_id', $workerId)->whereDate('clock_in_at', $today)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'weekHours'  => round(TimeLog::where('worker_id', $workerId)->whereBetween('clock_in_at', [now()->startOfWeek(), now()->endOfWeek()])->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'monthHours' => round(TimeLog::where('worker_id', $workerId)->whereMonth('clock_in_at', now()->month)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
        ];
    }

    private function timesheetRange(string $mode, int $offset): array
    {
        return match ($mode) {
            'daily'   => [now()->addDays($offset)->startOfDay(),   now()->addDays($offset)->endOfDay(),   now()->addDays($offset)->format('D, M d Y')],
            'monthly' => [now()->addMonths($offset)->startOfMonth(),now()->addMonths($offset)->endOfMonth(),now()->addMonths($offset)->format('F Y')],
            default   => [
                now()->addWeeks($offset)->startOfWeek(),
                now()->addWeeks($offset)->endOfWeek(),
                now()->addWeeks($offset)->startOfWeek()->format('M d') . ' - ' . now()->addWeeks($offset)->endOfWeek()->format('M d, Y'),
            ],
        };
    }
}