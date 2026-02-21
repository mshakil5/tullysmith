<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ServiceJob;
use App\Models\User;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class ServiceJobController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $jobs = ServiceJob::with('client:id,name', 'project:id,name')
                ->select([
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
                    'created_at'
                ])
                ->orderByDesc('id');

            return DataTables::of($jobs)
                ->addIndexColumn()

                ->addColumn('client', function ($row) {
                    return $row->client->name ?? '';
                })

                ->addColumn('project', function ($row) {
                    return $row->project->name ?? '';
                })

                ->addColumn('status', function ($row) {
                    $color = match ($row->status) {
                        'draft' => 'secondary',
                        'active' => 'success',
                        'pending' => 'warning',
                        'completed' => 'primary',
                        default => 'dark',
                    };
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->status) . '</span>';
                })

                ->addColumn('priority', function ($row) {
                    $color = match ($row->priority) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'secondary',
                    };
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->priority) . '</span>';
                })

                ->addColumn('start_datetime', function ($row) {
                    return $row->formattedStartDate();
                })

                ->addColumn('end_datetime', function ($row) {
                    return $row->formattedEndDate();
                })

                ->addColumn('estimated_hours', function ($row) {
                    return ($row->estimated_hours ?? 0) . ' hrs';
                })

                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                          <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                            <i class="ri-more-fill"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="' . route('serviceJob.show', $row->id) . '">
                                    <i class="ri-eye-fill me-2"></i>View
                                </a>
                            </li>
                            <li class="dropdown-divider"></li>
                            <li>
                              <button class="dropdown-item EditBtn" data-id="' . $row->id . '">
                                <i class="ri-pencil-fill me-2"></i>Edit
                              </button>
                            </li>
                            <li class="dropdown-divider"></li>
                            <li>
                              <button class="dropdown-item deleteBtn"
                                data-delete-url="' . route('serviceJob.delete', $row->id) . '"
                                data-method="DELETE"
                                data-table="#serviceJobTable">
                                <i class="ri-delete-bin-fill me-2"></i>Delete
                              </button>
                            </li>
                          </ul>
                        </div>';
                })

                ->rawColumns(['action', 'status', 'priority'])
                ->make(true);
        }

        $projects = Project::with('client:id,name')
            ->select('id', 'name', 'client_id')
            ->latest()
            ->get();

        $workers   = User::byRole('Worker')->get();

        return view('admin.service_jobs.index', compact('projects', 'workers'));
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

        if ($request->has('worker_ids')) {
            $job->workers()->sync($request->worker_ids);
        }

        return response()->json(['message' => 'Job created successfully.']);
    }

    public function edit($id)
    {
        $job = ServiceJob::with('client', 'project', 'workers')->findOrFail($id);
        $job->worker_ids = $job->workers->pluck('id');
        return response()->json($job);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:service_jobs,id',
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
        $job = ServiceJob::findOrFail($request->id);

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

        if ($request->has('worker_ids')) {
            $job->workers()->sync($request->worker_ids);
        } else {
            $job->workers()->detach();
        }

        return response()->json(['message' => 'Job updated successfully.']);
    }

    public function destroy($id)
    {
        $job = ServiceJob::findOrFail($id);
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully.']);
    }

    public function show($id)
    {
        $job = ServiceJob::with('client', 'project')->findOrFail($id);
        return view('admin.service_jobs.show', compact('job'));
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