<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $jobs    = ServiceJob::whereIn('status', ['active', 'pending', 'completed'])->select('id', 'job_title', 'job_id')->latest()->get();
        $workers = User::byRole('Worker')->select('id', 'name')->get();

        $assignmentsQuery = JobAssignment::with('job:id,job_title,job_id,status,client_id', 'job.client:id,name', 'worker:id,name');

        if ($request->filled('start') && $request->filled('end')) {
            $assignmentsQuery->whereBetween('assigned_date', [$request->start, $request->end]);
        }

        $assignments = $assignmentsQuery->get()->map(function ($a) {
            return [
                'id'             => $a->id,
                'service_job_id' => $a->service_job_id,
                'worker_id'      => $a->worker_id,
                'assigned_date'  => $a->assigned_date,
                'start_time'     => $a->start_time,
                'end_time'       => $a->end_time,
                'note'           => $a->note,
                'job_title'      => $a->job->job_title ?? '',
                'job_id'         => $a->job->job_id ?? '',
                'status'         => $a->job->status ?? '',
                'client_name'    => $a->job->client->name ?? '',
                'worker_name'    => $a->worker->name ?? '',
            ];
        });

        return response()->json([
            'jobs'        => $jobs,
            'workers'     => $workers,
            'assignments' => $assignments,
        ]);
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

        if ($this->hasTimeConflict($request->worker_id, $request->assigned_date, $request->start_time, $request->end_time)) {
            return response()->json([
                'message' => 'This worker is already assigned during this time slot on the selected date.'
            ], 422);
        }

        $assignment = JobAssignment::create($request->only([
            'service_job_id', 'worker_id', 'assigned_date', 'start_time', 'end_time', 'note'
        ]));

        return response()->json(['message' => 'Assignment created successfully.', 'id' => $assignment->id], 201);
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

        if ($this->hasTimeConflict($request->worker_id, $request->assigned_date, $request->start_time, $request->end_time, $id)) {
            return response()->json([
                'message' => 'This worker is already assigned during this time slot on the selected date.'
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

    private function hasTimeConflict($workerId, $assignedDate, $startTime, $endTime, $excludeId = null)
    {
        $query = JobAssignment::where('worker_id', $workerId)->where('assigned_date', $assignedDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($query->get() as $assign) {
            if (!$startTime || !$endTime || !$assign->start_time || !$assign->end_time) {
                return true;
            }
            if ($startTime < $assign->end_time && $endTime > $assign->start_time) {
                return true;
            }
        }

        return false;
    }
}