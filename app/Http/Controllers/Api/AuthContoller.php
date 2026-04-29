<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\ServiceJobChecklist;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthContoller extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error' => 'Unauthenticated'
            ], 401);
        }

        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        if (!$role) {
            Auth::logout();
            return response()->json([
                'message' => 'No role assigned.',
                'error' => 'Unauthorized'
            ], 403);
        }

        $token = $user->createToken('AppName')->accessToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'userId' => $user->id,
            'name' => $user->name,
            'role' => $role,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 200);
    }

    public function dashboard()
    {
        $user     = auth()->user();
        $today    = now()->toDateString();
        $isWorker = $user->hasRole('Worker');

        $activeJobs    = ServiceJob::where('status', 'active')->count();
        $pendingJobs   = ServiceJob::where('status', 'pending')->count();
        $completedJobs = ServiceJob::where('status', 'completed')->count();
        $totalClients  = User::where('user_type', 0)->count();
        $totalEmployees = User::where('user_type', 1)->count();
        $pendingApprovals =
            (int) ServiceJobChecklist::where('status', 'pending')->count()
        + (int) TimeLog::whereNotNull('clock_out_at')->where('status', 'pending')->count()
        + (int) ServiceJob::where('status', 'completed')->count()
        + (int) Document::where('status', 'pending')->count();

        $announcements = Announcement::with('job:id,job_title,job_id')
            ->where('status', 1)
            ->where(function ($q) use ($today) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $today);
            })
            ->when($isWorker, function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->whereNull('service_job_id')
                    ->orWhereHas('job.assignments', function ($q3) use ($user) {
                        $q3->where('worker_id', $user->id);
                    });
                });
            })
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->latest()
            ->get();

        $mapAssignment = function ($a) {
            return [
                'id'             => $a->id,
                'assigned_date'  => $a->assigned_date,
                'worker_name'    => $a->worker->name ?? '-',
                'job_title'      => $a->job->job_title ?? '',
                'job_id'         => $a->job->job_id ?? '',
                'client_name'    => $a->job->client->name ?? '-',
                'address'        => collect([
                    $a->job->address_line1,
                    $a->job->address_line2,
                    $a->job->city,
                    $a->job->postcode,
                ])->filter()->implode(', '),
                'status'         => $a->job->status ?? '',
                'priority'       => $a->job->priority ?? '',
                'note'           => $a->note,
                'service_job_id' => $a->service_job_id,
                'worker_id'      => $a->worker_id,
            ];
        };

        $baseQuery = JobAssignment::with([
            'job:id,job_title,job_id,address_line1,address_line2,city,postcode,status,priority,client_id',
            'job.client:id,name',
            'worker:id,name',
        ]);

        if ($isWorker) {
            $assignments = (clone $baseQuery)->where('worker_id', $user->id)->get()->map($mapAssignment);
            $todayJobs   = $assignments->where('assigned_date', $today)->count();
        } else {
            $assignments = (clone $baseQuery)->get()->map($mapAssignment);
            $todayJobs   = JobAssignment::where('assigned_date', $today)->count();
        }

        $jobs    = ServiceJob::whereIn('status', ['active', 'pending', 'completed'])->select('id', 'job_title', 'job_id')->latest()->get();
        $workers = User::byRole('Worker')->select('id', 'name')->get();

        return response()->json([
            'today_jobs'      => $todayJobs,
            'active_jobs'     => $activeJobs,
            'pending_jobs'    => $pendingJobs,
            'completed_jobs'  => $completedJobs,
            'total_clients'   => $totalClients,
            'total_employees' => $totalEmployees,
            'pending_approvals' => $pendingApprovals,
            'assignments'     => $assignments,
            'jobs'            => $jobs,
            'workers'         => $workers,
            'announcements'   => $announcements,
            'announcements_count' => $announcements->count()
        ]);
    }

    public function assignmentData(Request $request)
    {
        $assignments = JobAssignment::with([
            'job:id,job_title,job_id,address_line1,address_line2,city,postcode,status,priority,client_id',
            'job.client:id,name',
            'worker:id,name',
        ])
        ->whereBetween('assigned_date', [$request->start, $request->end])
        ->get()
        ->map(fn($a) => [
            'id'             => $a->id,
            'title'          => $a->worker->name . ' — ' . $a->job->job_title,
            'start'          => $a->assigned_date,
            'assigned_date'  => $a->assigned_date,
            'worker_name'    => $a->worker->name ?? '-',
            'job_title'      => $a->job->job_title ?? '',
            'job_id'         => $a->job->job_id ?? '',
            'client_name'    => $a->job->client->name ?? '-',
            'address'        => collect([
                $a->job->address_line1,
                $a->job->address_line2,
                $a->job->city,
                $a->job->postcode,
            ])->filter()->implode(', '),
            'status'         => $a->job->status ?? '',
            'priority'       => $a->job->priority ?? '',
            'note'           => $a->note,
            'service_job_id' => $a->service_job_id,
            'worker_id'      => $a->worker_id,
        ]);

        return response()->json($assignments);
    }

    public function assignmentStore(Request $request)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'worker_id'      => 'required|exists:users,id',
            'assigned_date'  => 'required|date',
            'note'           => 'nullable|string|max:500',
        ]);

        if ($this->hasConflict($request->worker_id, $request->assigned_date)) {
            return response()->json(['message' => 'This worker is already assigned on the selected date.'], 422);
        }

        $assignment = JobAssignment::create($request->only(['service_job_id', 'worker_id', 'assigned_date', 'note']));

        app(NotificationService::class)->sendToUser(
            userId: $request->worker_id,
            title:  'New Job Assigned',
            body: "You have been assigned a new job (ID: " . ServiceJob::find($request->service_job_id)->job_id . ") on " . Carbon::parse($request->assigned_date)->format('d F Y') . ".",
            type:   'job',
        );

        return response()->json(['message' => 'Assignment created successfully.', 'id' => $assignment->id], 201);
    }

    public function assignmentUpdate(Request $request, $id)
    {
        $request->validate([
            'service_job_id' => 'required|exists:service_jobs,id',
            'worker_id'      => 'required|exists:users,id',
            'assigned_date'  => 'required|date',
            'note'           => 'nullable|string|max:500',
        ]);

        if ($this->hasConflict($request->worker_id, $request->assigned_date, $id)) {
            return response()->json(['message' => 'Wokrer already assigned on this date.'], 422);
        }

        $assignment = JobAssignment::findOrFail($id);
        $assignment->update($request->only(['service_job_id', 'worker_id', 'assigned_date', 'note']));

        app(NotificationService::class)->sendToUser(
            userId: $request->worker_id,
            title:  'Job Assignment Updated',
            body: "Your assignment for job (ID: " . ServiceJob::find($request->service_job_id)->job_id . ") has been updated to " . Carbon::parse($request->assigned_date)->format('d F Y') . ".",
            type:   'job',
        );

        return response()->json(['message' => 'Assignment updated successfully.']);
    }

    public function assignmentDestroy($id)
    {
        JobAssignment::findOrFail($id)->delete();
        return response()->json(['message' => 'Assignment deleted successfully.']);
    }

    private function hasConflict($workerId, $assignedDate, $excludeId = null)
    {
        $query = JobAssignment::where('worker_id', $workerId)->where('assigned_date', $assignedDate);
        if ($excludeId) $query->where('id', '!=', $excludeId);
        return $query->exists();
    }
}
