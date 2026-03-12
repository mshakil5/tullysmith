<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistAnswer;
use App\Models\JobAssignment;
use App\Models\ServiceJobChecklist;
use App\Models\TimeLog;
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

        return response()->json([
            'todayAssignments' => $todayAssignments,
            'activeLog'        => $activeLog ? $this->formatLog($activeLog) : null,
            'recentLogs'       => $recentLogs->map(fn($l) => $this->formatLog($l)),
            'todayHours'       => round(TimeLog::where('worker_id', $workerId)->whereDate('clock_in_at', $today)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'weekHours'        => round(TimeLog::where('worker_id', $workerId)->whereBetween('clock_in_at', [now()->startOfWeek(), now()->endOfWeek()])->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'monthHours'       => round(TimeLog::where('worker_id', $workerId)->whereMonth('clock_in_at', now()->month)->whereNotNull('clock_out_at')->sum('total_hours'), 2),
        ]);
    }

    public function clockIn(Request $request)
    {
        $request->validate([
            'job_assignment_id' => 'required|exists:job_assignments,id',
            'photo'             => 'required|string',
            'lat'               => 'nullable|numeric',
            'lng'               => 'nullable|numeric',
            'force'             => 'nullable|boolean',
        ]);

        $workerId = auth()->id();
        $today    = now()->toDateString();

        if (TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->exists()) {
            return response()->json(['message' => 'You already have an active clock-in. Please clock out first.'], 422);
        }

        $assignment = JobAssignment::with('job:id,job_title,job_id,postcode')->findOrFail($request->job_assignment_id);
        if ($assignment->assigned_date !== $today)      return response()->json(['message' => 'This job is not assigned for today.'], 422);
        if ($assignment->worker_id !== $workerId)       return response()->json(['message' => 'Unauthorized.'], 403);

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

        $clockInTime = now();

        $log = TimeLog::create([
            'worker_id'         => $workerId,
            'service_job_id'    => $assignment->service_job_id,
            'job_assignment_id' => $assignment->id,
            'clock_in_at'       => $clockInTime,
            'clock_in_photo'    => $this->savePhoto($request->photo, 'clockin', $workerId, $clockInTime->format('d M Y h:i A'), 'Clock In'),
            'clock_in_lat'      => $request->lat,
            'clock_in_lng'      => $request->lng,
            'location_note'     => $locationMsg,
        ]);

        $msg = 'Clocked in successfully.';
        if ($locationMsg === 'location_verified')                         $msg .= ' Location verified ✓';
        elseif ($locationMsg && $locationMsg !== 'location_check_failed') $msg .= ' Note: ' . $locationMsg . '.';

        return response()->json([
            'message' => $msg,
            'log'     => $this->formatLog($log->load('job')),
        ]);
    }

    public function clockOut(Request $request)
    {
        $request->validate(['photo' => 'required|string']);

        $workerId = auth()->id();
        $log      = TimeLog::where('worker_id', $workerId)->whereNull('clock_out_at')->latest()->firstOrFail();

        $clockOut   = now();
        $totalHours = round($log->clock_in_at->diffInMinutes($clockOut) / 60, 2);

        $log->update([
            'clock_out_at'    => $clockOut,
            'clock_out_photo' => $this->savePhoto($request->photo, 'clockout', $workerId, $clockOut->format('d M Y h:i A'), 'Clock Out'),
            'total_hours'     => $totalHours,
        ]);

        return response()->json([
            'message' => 'Clocked out. Total: ' . number_format($totalHours, 1) . 'h',
            'log'     => $this->formatLog($log->fresh()->load('job')),
        ]);
    }

    public function getClockChecklists(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'type'           => 'required|in:clock_in,clock_out',
        ]);

        $checklists = ServiceJobChecklist::where('service_job_id', $request->service_job_id)
            ->where('show_at', $request->type)
            ->with(['checklist.items', 'answers' => function ($q) {
                $q->where('answered_by', auth()->id());
            }, 'answers.answeredBy'])
            ->get();

        if ($checklists->isEmpty()) {
            return response()->json(['has_checklists' => false]);
        }

        $groups = $checklists->map(function ($assignment) {
            $existingAnswers = $assignment->answers->keyBy('checklist_item_id');

            return [
                'id'    => $assignment->id,
                'title' => $assignment->checklist->title,
                'items' => $assignment->checklist->items->map(function ($item) use ($existingAnswers) {
                    $existing = $existingAnswers->get($item->id);
                    return [
                        'id'                  => $item->id,
                        'question'            => $item->question,
                        'type'                => $item->type,
                        'is_required'         => (bool) $item->is_required,
                        'existing_answer'     => $existing?->answer,
                        'existing_photo_path' => $existing?->photo_path ? url($existing->photo_path) : null,
                        'answered_by'         => $existing?->answeredBy?->name,
                        'answered_at'         => $existing?->updated_at?->format('d M Y, h:i A'),
                    ];
                }),
            ];
        });

        return response()->json([
            'has_checklists' => true,
            'groups'         => $groups,
        ]);
    }

    public function saveClockChecklistAnswers(Request $request)
    {
        $answers = $request->input('answers', []);
        $photos  = $request->file('photos', []);

        foreach ($answers as $assignmentId => $items) {
            foreach ($items as $itemId => $answer) {
                ChecklistAnswer::updateOrCreate(
                    ['service_job_checklist_id' => $assignmentId, 'checklist_item_id' => $itemId],
                    ['answer' => $answer, 'answered_by' => auth()->id()]
                );
            }
        }

        foreach ($photos as $assignmentId => $items) {
            foreach ($items as $itemId => $file) {
                $filename = 'checklist_' . $assignmentId . '_' . $itemId . '_' . mt_rand(10000000, 99999999) . '.webp';
                $path     = public_path('uploads/checklist-answers/');
                if (!file_exists($path)) mkdir($path, 0755, true);
                Image::make($file)->encode('webp', 75)->save($path . $filename);
                $photoPath = '/uploads/checklist-answers/' . $filename;

                ChecklistAnswer::updateOrCreate(
                    ['service_job_checklist_id' => $assignmentId, 'checklist_item_id' => $itemId],
                    ['answer' => $photoPath, 'photo_path' => $photoPath, 'answered_by' => auth()->id()]
                );
            }
        }

        $assignmentIds = array_keys($answers + $photos);
        $missing = [];

        foreach ($assignmentIds as $assignmentId) {
            $assignment = ServiceJobChecklist::with('checklist.items', 'answers')->find($assignmentId);
            if (!$assignment) continue;

            $answeredItemIds = $assignment->answers->pluck('checklist_item_id')->toArray();
            foreach ($assignment->checklist->items as $item) {
                if ($item->is_required && !in_array($item->id, $answeredItemIds)) {
                    $missing[] = $item->question;
                }
            }
        }

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'missing' => $missing,
                'message' => 'Some required questions are unanswered.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function timesheet(Request $request)
    {
        $workerId = auth()->id();
        $mode     = $request->input('mode', 'weekly');
        $offset   = (int) $request->input('offset', 0);

        [$start, $end, $label] = $this->timesheetRange($mode, $offset);

        $logs = TimeLog::with('job:id,job_title')
            ->where('worker_id', $workerId)
            ->whereBetween('clock_in_at', [$start, $end])
            ->orderByDesc('clock_in_at')
            ->get();

        return response()->json([
            'label'      => $label,
            'start'      => $start->toDateString(),
            'end'        => $end->toDateString(),
            'totalHours' => round($logs->whereNotNull('clock_out_at')->sum('total_hours'), 2),
            'logs'       => $logs->map(fn($l) => $this->formatLog($l)),
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
            'clock_in_lat'          => $log->clock_in_lat,
            'clock_in_lng'          => $log->clock_in_lng,
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

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $R = 6371000;
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function timesheetRange(string $mode, int $offset): array
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