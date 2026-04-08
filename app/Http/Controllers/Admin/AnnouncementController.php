<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Services\NotificationService;
use DataTables;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $announcements = Announcement::with('job:id,job_title,job_id')->latest();

            return DataTables::of($announcements)
                ->addIndexColumn()
                ->addColumn('priority', function ($row) {
                    $color = match ($row->priority) {
                        'low'    => 'success',
                        'medium' => 'warning',
                        'high'   => 'danger',
                        default  => 'secondary',
                    };
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row->priority) . '</span>';
                })
                ->addColumn('job', function ($row) {
                    return $row->job ? $row->job->job_id . ' — ' . $row->job->job_title : '-';
                })
                ->addColumn('expires_at', function ($row) {
                    return $row->expires_at ? $row->expires_at->format('M d, Y') : '-';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="form-check form-switch">
                                <input class="form-check-input toggle-status" data-id="' . $row->id . '" type="checkbox" ' . $checked . '>
                            </div>';
                })
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown">
                          <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown">
                            <i class="ri-more-fill"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                              <button class="dropdown-item EditBtn" data-id="' . $row->id . '">
                                <i class="ri-pencil-fill me-2"></i>Edit
                              </button>
                            </li>
                            <li class="dropdown-divider"></li>
                            <li>
                              <button class="dropdown-item deleteBtn"
                                data-delete-url="' . route('announcement.delete', $row->id) . '"
                                data-method="DELETE"
                                data-table="#announcementTable">
                                <i class="ri-delete-bin-fill me-2"></i>Delete
                              </button>
                            </li>
                          </ul>
                        </div>';
                })
                ->rawColumns(['priority', 'status', 'action'])
                ->make(true);
        }

        $jobs = ServiceJob::whereIn('status', ['active', 'pending'])->select('id', 'job_title', 'job_id')->latest()->get();

        return view('admin.announcement.index', compact('jobs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'priority'       => 'required|in:low,medium,high',
            'service_job_id' => 'nullable|exists:service_jobs,id',
            'expires_at'     => 'nullable|date',
        ]);

        $announcement = Announcement::create($request->only('title', 'content', 'priority', 'service_job_id', 'expires_at'));

        if ($announcement->service_job_id) {
            $workerIds = JobAssignment::where('service_job_id', $announcement->service_job_id)
                ->pluck('worker_id')
                ->unique()
                ->values()
                ->all();

            if (!empty($workerIds)) {
                app(NotificationService::class)->sendToUsers(
                    userIds: $workerIds,
                    title:   'New Announcement',
                    body:    $announcement->title,
                    type:    'announcement',
                );
            }
        } else {
            app(NotificationService::class)->sendToAll(
                title: 'New Announcement',
                body:  $announcement->title,
                type:  'announcement',
            );
        }

        return response()->json(['message' => 'Announcement created successfully.']);
    }

    public function edit($id)
    {
        return response()->json(Announcement::findOrFail($id));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:announcements,id',
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'priority'       => 'required|in:low,medium,high',
            'service_job_id' => 'nullable|exists:service_jobs,id',
            'expires_at'     => 'nullable|date',
        ]);

        $announcement = Announcement::findOrFail($request->id);
        $announcement->update($request->only('title', 'content', 'priority', 'service_job_id', 'expires_at'));

        if ($announcement->service_job_id) {
            $workerIds = JobAssignment::where('service_job_id', $announcement->service_job_id)
                ->pluck('worker_id')
                ->unique()
                ->values()
                ->all();

            if (!empty($workerIds)) {
                app(NotificationService::class)->sendToUsers(
                    userIds: $workerIds,
                    title:   'Announcement Updated',
                    body:    $announcement->title,
                    type:    'announcement',
                );
            }
        } else {
            app(NotificationService::class)->sendToAll(
                title: 'Announcement Updated',
                body:  $announcement->title,
                type:  'announcement',
            );
        }

        return response()->json(['message' => 'Announcement updated successfully.']);
    }

    public function destroy($id)
    {
        Announcement::findOrFail($id)->delete();
        return response()->json(['message' => 'Announcement deleted successfully.']);
    }

    public function toggleStatus(Request $request)
    {
        $announcement = Announcement::findOrFail($request->id);
        $announcement->status = $request->status;
        $announcement->save();
        return response()->json(['message' => 'Status updated successfully.']);
    }
}