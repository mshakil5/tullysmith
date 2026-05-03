<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function dashboard()
    {
        if (Auth::check()) {
            $user = auth()->user();

            if (in_array($user->user_type, [1, 2, 3])) {
                return redirect()->route('admin.dashboard');
            } else if ($user->user_type == '0') {
                return redirect()->route('client.dashboard');
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function adminHome()
    {
        $workerId = auth()->id();
        $today    = now()->toDateString();

        $totalWorker       = User::byRole('Worker')->count();
        $activeJobs        = ServiceJob::where('status', 'active')->count();
        $draftJobs         = ServiceJob::where('status', 'draft')->count();
        $todaysAssignments = JobAssignment::where('assigned_date', $today)->count();

        $jobs    = ServiceJob::whereIn('status', ['active', 'pending', 'completed'])->select('id', 'job_title', 'job_id')->latest()->get();
        $workers = User::byRole('Worker')->select('id', 'name')->get();

        $announcements = Announcement::with('job')
            ->where('status', 1)
            ->where(function ($q) use ($today) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $today);
            })
            ->where(function ($q) use ($workerId) {
                $q->whereNull('service_job_id')
                ->orWhereHas('job.assignments', function ($q2) use ($workerId) {
                    $q2->where('worker_id', $workerId);
                });
            })
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->latest()
            ->get();

        $mapAssignment = function ($a) {
            return [
                'id'             => $a->id,
                'title'          => $a->worker->name . ' — ' . $a->job->job_title,
                'start'          => $a->assigned_date,
                'assigned_date'  => $a->assigned_date,
                'worker_name'    => $a->worker->name ?? '-',
                'job_title'      => $a->job->job_title,
                'job_id'         => $a->job->job_id,
                'client_name'    => $a->job->client->name ?? '-',
                'address'        => collect([$a->job->address_line1, $a->job->address_line2, $a->job->city, $a->job->postcode])->filter()->implode(', '),
                'status'         => $a->job->status,
                'priority'       => $a->job->priority,
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

        $myAssignments = (clone $baseQuery)->where('worker_id', $workerId)->get()->map(fn($a) => array_merge($mapAssignment($a), [
            'title'           => $a->job->job_title,
            'backgroundColor' => '#16a34a',
            'borderColor'     => '#15803d',
            'textColor'       => '#ffffff',
        ]));

        // ── NEW: all assignments for live dropdown status ──
        $allAssignments = JobAssignment::with([
            'job:id,job_title,job_id',
            'worker:id,name',
        ])->get()->map(fn($a) => [
            'id'             => $a->id,
            'service_job_id' => $a->service_job_id,
            'worker_id'      => $a->worker_id,
            'assigned_date'  => $a->assigned_date,
            'worker_name'    => $a->worker->name ?? '-',
            'job_title'      => $a->job->job_title ?? '-',
            'job_id'         => $a->job->job_id ?? '-',
        ]);

        return view('admin.pages.dashboard', compact(
            'totalWorker', 'activeJobs', 'draftJobs', 'todaysAssignments',
            'myAssignments', 'jobs', 'workers', 'announcements', 'allAssignments'
        ));
    }
    
    public function assignmentData(Request $request)
    {
        $assignments = JobAssignment::with('job:id,job_title,job_id', 'worker:id,name')
            ->whereBetween('assigned_date', [$request->start, $request->end])
            ->get()
            ->map(fn($a) => [
                'id'              => $a->id,
                'title'           => $a->worker->name . ' — ' . $a->job->job_title,
                'start'           => $a->assigned_date,
                'assigned_date'   => $a->assigned_date,
                'worker_name'     => $a->worker->name,
                'job_title'       => $a->job->job_title,
                'job_id'          => $a->job->job_id,
                'client_name'     => $a->job->client->name ?? '-',
                'address'         => collect([$a->job->address_line1, $a->job->address_line2, $a->job->city, $a->job->postcode])->filter()->implode(', '),
                'status'          => $a->job->status,
                'priority'        => $a->job->priority,
                'note'            => $a->note,
                'service_job_id'  => $a->service_job_id,
                'worker_id'       => $a->worker_id,
                'textColor'       => '#ffffff',
                'backgroundColor' => '#405189',
                'borderColor'     => '#2c3e75',
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

        return response()->json(['message' => 'Assignment created successfully.']);
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
            return response()->json(['message' => 'This worker is already assigned on the selected date.'], 422);
        }

        $assignment = JobAssignment::findOrFail($id);
        $assignment->update($request->only(['service_job_id', 'worker_id', 'assigned_date', 'note']));

        app(NotificationService::class)->sendToUser(
            userId: $request->worker_id,
            title:  'Job Updated',
            body: "Your job assignment (ID: " . ServiceJob::find($request->service_job_id)->job_id . ") on " . Carbon::parse($request->assigned_date)->format('d F Y') . " has been updated.",
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

    public function userHome()
    {
        return 'user';
    }

    public function cleanDB()
    {
        $tables = [
            'activity_log',
            'announcements',
            'checklists',
            'checklist_answers',
            'checklist_items',
            'contacts',
            'documents',
            'job_assignments',
            'notes',
            'notifications',
            'service_jobs',
            'service_job_checklists',
            'sessions',
            'time_logs',
            'users',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return "Cleaned successfully.";
    }
}