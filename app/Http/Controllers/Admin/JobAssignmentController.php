<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Http\Request;

class JobAssignmentController extends Controller
{
    public function index()
    {
        $jobs    = ServiceJob::whereIn('status', ['active', 'pending'])->select('id', 'job_title', 'job_id')->latest()->get();
        $workers = User::byRole('Worker')->select('id', 'name')->get();
        return view('admin.job_assignments.index', compact('jobs', 'workers'));
    }

    public function data(Request $request)
    {
        $start = $request->start;
        $end   = $request->end;

        $assignments = JobAssignment::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('assigned_date', [$start, $end])
            ->get()
            ->map(function ($a) {
                return [
                    'id'             => $a->id,
                    'title'          => $a->worker->name . ' — ' . $a->job->job_title,
                    'start'          => $a->assigned_date . ($a->start_time ? 'T' . $a->start_time : ''),
                    'end'            => $a->assigned_date . ($a->end_time ? 'T' . $a->end_time : ''),
                    'assigned_date'  => $a->assigned_date,
                    'worker_name'    => $a->worker->name,
                    'job_title'      => $a->job->job_title,
                    'job_id'         => $a->job->job_id,
                    'start_time'     => $a->start_time,
                    'end_time'       => $a->end_time,
                    'note'           => $a->note,
                    'service_job_id' => $a->service_job_id,
                    'worker_id'      => $a->worker_id,
                ];
            });

        return response()->json($assignments);
    }

    private function hasTimeConflict($workerId, $assignedDate, $startTime, $endTime, $excludeId = null)
    {
        $query = JobAssignment::where('worker_id', $workerId)
            ->where('assigned_date', $assignedDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId); // skip self when updating
        }

        $existing = $query->get();

        foreach ($existing as $assign) {
            // If either has no time → treat as full day conflict (strict policy)
            if (!$startTime || !$endTime || !$assign->start_time || !$assign->end_time) {
                return true; // conflict - one or both are full-day
            }

            // Both have times → check overlap
            $newStart = $startTime;
            $newEnd   = $endTime;
            $existStart = $assign->start_time;
            $existEnd   = $assign->end_time;

            // Overlap condition
            if ($newStart < $existEnd && $newEnd > $existStart) {
                return true;
            }
        }

        return false;
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'worker_id'      => 'required|exists:users,id',
            'assigned_date'  => 'required|date',
            'start_time'     => 'nullable|date_format:H:i',
            'end_time'       => 'nullable|date_format:H:i|after:start_time',
            'note'           => 'nullable|string|max:500',
        ], [
            'end_time.after' => 'End time must be after start time.',
        ]);

        $workerId     = $request->worker_id;
        $assignedDate = $request->assigned_date;
        $startTime    = $request->start_time;
        $endTime      = $request->end_time;

        if ($this->hasTimeConflict($workerId, $assignedDate, $startTime, $endTime)) {
            return response()->json([
                'message' => 'This staff member is already assigned during this time slot on the selected date.'
            ], 422);
        }

        JobAssignment::create([
            'service_job_id' => $request->service_job_id,
            'worker_id'      => $workerId,
            'assigned_date'  => $assignedDate,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'note'           => $request->note,
        ]);

        return response()->json(['message' => 'Assignment created successfully.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'worker_id'      => 'required|exists:users,id',
            'assigned_date'  => 'required|date',
            'start_time'     => 'nullable|date_format:H:i',
            'end_time'       => 'nullable|date_format:H:i|after:start_time',
            'note'           => 'nullable|string|max:500',
        ], [
            'end_time.after' => 'End time must be after start time.',
        ]);

        $workerId     = $request->worker_id;
        $assignedDate = $request->assigned_date;
        $startTime    = $request->start_time;
        $endTime      = $request->end_time;

        if ($this->hasTimeConflict($workerId, $assignedDate, $startTime, $endTime, $id)) {
            return response()->json([
                'message' => 'This staff member is already assigned during this time slot on the selected date.'
            ], 422);
        }

        JobAssignment::findOrFail($id)->update($request->only([
            'service_job_id', 'worker_id', 'assigned_date', 'start_time', 'end_time', 'note'
        ]));

        return response()->json(['message' => 'Assignment updated successfully.']);
    }

    public function destroy($id)
    {
        JobAssignment::findOrFail($id)->delete();
        return response()->json(['message' => 'Assignment deleted successfully.']);
    }
}