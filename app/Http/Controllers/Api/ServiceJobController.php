<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceJobController extends Controller
{
    public function index()
    {
        $jobs = ServiceJob::with('client:id,name', 'project:id,name')
            ->orderByDesc('id')
            ->get([
                'id',
                'job_id',
                'job_title',
                'client_id',
                'project_id',
                'address',
                'status',
                'priority',
                'start_datetime',
                'end_datetime',
                'estimated_hours',
                'description',
                'instructions',
                'created_at'
            ]);

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:projects,id',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'status' => 'required|string|max:50',
            'priority' => 'required|string|max:50',
            'start_datetime' => 'nullable|date_format:Y-m-d\TH:i',
            'end_datetime' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $project = Project::findOrFail($request->project_id);

        $jobId = 'JOB-' . time();

        $estimatedHours = $this->calculateHours($request->start_datetime, $request->end_datetime);

        $job = ServiceJob::create([
            'job_id' => $jobId,
            'job_title' => $request->job_title,
            'client_id' => $project->client_id,
            'project_id' => $request->project_id,
            'address' => $request->address,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'status' => $request->status,
            'priority' => $request->priority,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'estimated_hours' => $estimatedHours,
        ]);

        return response()->json([
            'message' => 'Job created successfully.',
            'job' => $job
        ]);
    }

    public function show($id)
    {
        $job = ServiceJob::with('client:id,name', 'project:id,name')->findOrFail($id);
        return response()->json($job);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:projects,id',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'status' => 'required|string|max:50',
            'priority' => 'required|string|max:50',
            'start_datetime' => 'nullable|date_format:Y-m-d\TH:i',
            'end_datetime' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $job = ServiceJob::findOrFail($id);
        $project = Project::findOrFail($request->project_id);

        $estimatedHours = $this->calculateHours($request->start_datetime, $request->end_datetime);

        $job->update([
            'job_title' => $request->job_title,
            'client_id' => $project->client_id,
            'project_id' => $request->project_id,
            'address' => $request->address,
            'description' => $request->description,
            'instructions' => $request->instructions,
            'status' => $request->status,
            'priority' => $request->priority,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'estimated_hours' => $estimatedHours,
        ]);

        return response()->json([
            'message' => 'Job updated successfully.',
            'job' => $job
        ]);
    }

    public function destroy($id)
    {
        $job = ServiceJob::findOrFail($id);
        $job->delete();

        return response()->json([
            'message' => 'Job deleted successfully.'
        ]);
    }

    private function calculateHours($startDateTime, $endDateTime)
    {
        if (!$startDateTime || !$endDateTime) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m-d\TH:i', $startDateTime);
        $end = Carbon::createFromFormat('Y-m-d\TH:i', $endDateTime);

        return round($start->diffInMinutes($end) / 60, 2);
    }
}