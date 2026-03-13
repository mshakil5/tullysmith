<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class AdminTimeController extends Controller
{
    public function workers()
    {
        if (auth()->user()->hasRole('Worker')) abort(403);

        $workers = User::byRole('Worker')->select('id', 'name')->orderBy('name')->get();

        return response()->json(['workers' => $workers]);
    }

    public function workerData(Request $request)
    {
        if (auth()->user()->hasRole('Worker')) abort(403);

        $request->validate(['worker_id' => 'required|exists:users,id']);

        $workerId = $request->worker_id;
        $worker   = User::byRole('Worker')->findOrFail($workerId);
        $today    = now()->toDateString();

        $todayAssignments = JobAssignment::with('job:id,job_title,job_id,postcode,address_line1,address_line2,city,status')
            ->where('worker_id', $workerId)
            ->where('assigned_date', $today)
            ->get()
            ->map(fn($a) => [
                'id'             => $a->id,
                'service_job_id' => $a->service_job_id,
                'start_time'     => $a->start_time,
                'end_time'       => $a->end_time,
                'job'            => [
                    'id'            => $a->job->id,
                    'job_title'     => $a->job->job_title,
                    'job_id'        => $a->job->job_id,
                    'postcode'      => $a->job->postcode,
                    'address_line1' => $a->job->address_line1,
                    'address_line2' => $a->job->address_line2,
                    'city'          => $a->job->city,
                ],
            ]);

        $activeLog  = TimeLog::with('job:id,job_title')->where('worker_id', $workerId)->whereNull('clock_out_at')->latest()->first();
        $recentLogs = TimeLog::with('job:id,job_title')->where('worker_id', $workerId)->latest()->take(10)->get();

        $today = now()->toDateString();

        return response()->json([
            'worker'            => ['id' => $worker->id, 'name' => $worker->name],
            'todayAssignments'  => $todayAssignments,
            'activeLog'         => $activeLog  ? $this->formatLog($activeLog)  : null,
            'recentLogs'        => $recentLogs->map(fn($l) => $this->formatLog($l)),
            'todayHours'        => round(TimeLog::where('worker_id', $workerId)->whereDate('clock_in_at', $today)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'weekHours'         => round(TimeLog::where('worker_id', $workerId)->whereBetween('clock_in_at', [now()->startOfWeek(), now()->endOfWeek()])->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'monthHours'        => round(TimeLog::where('worker_id', $workerId)->whereMonth('clock_in_at', now()->month)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
        ]);
    }

    public function manualClockIn(Request $request)
    {
        if (auth()->user()->hasRole('Worker')) abort(403);

        $request->validate([
            'worker_id'         => 'required|exists:users,id',
            'job_assignment_id' => 'required|exists:job_assignments,id',
            'clock_in_at'       => 'required|date',
            'clock_out_at'      => 'nullable|date|after:clock_in_at',
            'clock_in_photo'    => 'nullable|string',
            'clock_out_photo'   => 'nullable|string',
        ], [
            'clock_out_at.after' => 'Clock out must be after clock in.',
        ]);

        $workerId   = $request->worker_id;
        $assignment = JobAssignment::findOrFail($request->job_assignment_id);

        if ($assignment->worker_id != $workerId) {
            return response()->json(['message' => 'This assignment does not belong to the selected worker.'], 422);
        }

        if (TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->exists()) {
            return response()->json(['message' => 'This worker already has an active clock-in. Please clock out first.'], 422);
        }

        $clockIn    = Carbon::parse($request->clock_in_at);
        $clockOut   = $request->clock_out_at ? Carbon::parse($request->clock_out_at) : null;
        $totalHours = $clockOut ? round($clockIn->diffInMinutes($clockOut) / 60, 2) : null;

        $data = [
            'worker_id'         => $workerId,
            'service_job_id'    => $assignment->service_job_id,
            'job_assignment_id' => $assignment->id,
            'clock_in_at'       => $clockIn,
            'clock_out_at'      => $clockOut,
            'total_hours'       => $totalHours,
            'location_note'     => 'Admin entry',
        ];

        if ($request->filled('clock_in_photo')) {
            $data['clock_in_photo'] = $this->savePhoto(
                $request->clock_in_photo, 'clockin', $workerId,
                $clockIn->format('d M Y h:i A'), 'Clock In'
            );
        }

        if ($request->filled('clock_out_photo') && $clockOut) {
            $data['clock_out_photo'] = $this->savePhoto(
                $request->clock_out_photo, 'clockout', $workerId,
                $clockOut->format('d M Y h:i A'), 'Clock Out'
            );
        }

        $log = TimeLog::create($data);

        return response()->json([
            'message' => 'Clock in recorded successfully.',
            'log'     => $this->formatLog($log->load('job')),
        ]);
    }

    public function clockOut(Request $request)
    {
        if (auth()->user()->hasRole('Worker')) abort(403);

        $request->validate([
            'worker_id'       => 'required|exists:users,id',
            'clock_out_photo' => 'nullable|string',
        ]);

        $workerId = $request->worker_id;
        $log      = TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->latest()->first();

        if (!$log) {
            return response()->json(['message' => 'No active clock-in found for this worker.'], 422);
        }

        $clockOut   = now();
        $totalHours = round($log->clock_in_at->diffInMinutes($clockOut) / 60, 2);

        $data = [
            'clock_out_at' => $clockOut,
            'total_hours'  => $totalHours,
            'location_note' => $log->location_note ?? 'Admin entry',
        ];

        if ($request->filled('clock_out_photo')) {
            $data['clock_out_photo'] = $this->savePhoto(
                $request->clock_out_photo, 'clockout', $workerId,
                $clockOut->format('d M Y h:i A'), 'Clock Out'
            );
        }

        $log->update($data);

        return response()->json([
            'message' => 'Clocked out successfully. Total: ' . number_format($totalHours, 1) . 'h',
            'log'     => $this->formatLog($log->fresh()->load('job')),
        ]);
    }

    private function formatLog(TimeLog $log): array
    {
        $placeholder = url('/images/placeholder.webp');

        return [
            'id'                    => $log->id,
            'service_job_id'        => $log->service_job_id,
            'job_assignment_id'     => $log->job_assignment_id,
            'clock_in_at'           => $log->clock_in_at?->toIso8601String(),
            'clock_out_at'          => $log->clock_out_at?->toIso8601String(),
            'clock_in_time'         => $log->clock_in_at?->format('h:i A'),
            'clock_out_time'        => $log->clock_out_at?->format('h:i A'),
            'total_hours'           => $log->total_hours,
            'total_hours_formatted' => $log->total_hours ? number_format($log->total_hours, 2) . 'h' : null,
            'clock_in_photo'        => $log->clock_in_photo  ? url($log->clock_in_photo)  : $placeholder,
            'clock_out_photo'       => $log->clock_out_photo ? url($log->clock_out_photo) : null,
            'location_note'         => $log->location_note,
            'status'                => $log->status,
            'job'                   => $log->job ? ['id' => $log->job->id, 'job_title' => $log->job->job_title] : null,
        ];
    }

    private function savePhoto(string $base64, string $prefix, int $workerId, ?string $timestamp = null, string $label = ''): ?string
    {
        if (!$base64) return null;

        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $file = $prefix . '_' . $workerId . '_' . mt_rand(10000000, 99999999) . '.webp';
        $path = public_path('uploads/time-logs/');
        if (!file_exists($path)) mkdir($path, 0755, true);

        $img       = Image::make($data);
        $timestamp = $timestamp ?? now()->format('d M Y h:i A');
        $width     = $img->width();
        $height    = $img->height();

        $img->rectangle(0, $height - 40, $width, $height, function ($draw) {
            $draw->background('rgba(0, 0, 0, 0.5)');
        });

        $img->text(($label ? $label . '  ' : '') . $timestamp, $width / 2, $height - 13, function ($font) {
            $font->file(public_path('resources/backend/fonts/arial.ttf'));
            $font->size(18);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        $img->encode('webp', 60)->save($path . $file);
        return '/uploads/time-logs/' . $file;
    }
}