<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceJob;
use App\Models\User;
use DataTables;
use Illuminate\Http\Request;

class ServiceJobController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $jobs = ServiceJob::with('client:id,name')
                ->select([
                    'id', 'job_id', 'job_title', 'client_id',
                    'address_line1', 'address_line2', 'city', 'postcode',
                    'status', 'priority', 'start_date', 'end_date',
                    'estimated_hours', 'created_at'
                ])
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderByDesc('id');

            return DataTables::of($jobs)
                ->addIndexColumn()

                ->addColumn('client', function ($row) {
                    return $row->client->name ?? '';
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

                ->addColumn('start_date', function ($row) {
                    return $row->formattedStartDate();
                })

                ->addColumn('end_date', function ($row) {
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

        $clients = User::where('user_type', 0)->select('id', 'name')->latest()->get();

        return view('admin.service_jobs.index', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_title'      => 'required|string|max:255',
            'client_id'      => 'required|integer|exists:users,id',
            'description'    => 'nullable|string',
            'instructions'   => 'nullable|string',
            'status'         => 'required|string|max:50',
            'priority'       => 'required|string|max:50',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'required|string|max:20',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric',
        ]);

        ServiceJob::create([
            'job_id'          => 'JOB-' . time(),
            'job_title'       => $request->job_title,
            'client_id'       => $request->client_id,
            'description'     => $request->description,
            'instructions'    => $request->instructions,
            'status'          => $request->status,
            'priority'        => $request->priority,
            'address_line1'   => $request->address_line1,
            'address_line2'   => $request->address_line2,
            'city'            => $request->city,
            'postcode'        => $request->postcode,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'estimated_hours' => $request->estimated_hours,
        ]);

        return response()->json(['message' => 'Job created successfully.']);
    }

    public function edit($id)
    {
        $job = ServiceJob::with('client')->findOrFail($id);
        return response()->json($job);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:service_jobs,id',
            'job_title'      => 'required|string|max:255',
            'client_id'      => 'required|integer|exists:users,id',
            'description'    => 'nullable|string',
            'instructions'   => 'nullable|string',
            'status'         => 'required|string|max:50',
            'priority'       => 'required|string|max:50',
            'address_line1'  => 'nullable|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'required|string|max:20',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'estimated_hours'=> 'nullable|numeric',
        ]);

        $job = ServiceJob::findOrFail($request->id);

        $job->update([
            'job_title'       => $request->job_title,
            'client_id'       => $request->client_id,
            'description'     => $request->description,
            'instructions'    => $request->instructions,
            'status'          => $request->status,
            'priority'        => $request->priority,
            'address_line1'   => $request->address_line1,
            'address_line2'   => $request->address_line2,
            'city'            => $request->city,
            'postcode'        => $request->postcode,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'estimated_hours' => $request->estimated_hours,
        ]);

        return response()->json(['message' => 'Job updated successfully.']);
    }

    public function destroy($id)
    {
        ServiceJob::findOrFail($id)->delete();
        return response()->json(['message' => 'Job deleted successfully.']);
    }

    public function show($id)
    {
        $job = ServiceJob::with('client', 'assignments.worker', 'timeLogs.worker')->findOrFail($id);
        return view('admin.service_jobs.show', compact('job'));
    }
}