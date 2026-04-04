<?php

namespace App\Http\Controllers;

use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $pendingJobs       = ServiceJob::where('status', 'pending')->count();
        $todaysAssignments = JobAssignment::where('assigned_date', $today)->count();

        $jobs    = ServiceJob::whereIn('status', ['active', 'pending', 'completed'])->select('id', 'job_title', 'job_id')->latest()->get();
        $workers = User::byRole('Worker')->select('id', 'name')->get();

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

        return view('admin.pages.dashboard', compact(
            'totalWorker', 'activeJobs', 'pendingJobs', 'todaysAssignments',
            'myAssignments', 'jobs', 'workers'
        ));
    }

    private function hasConflict($workerId, $assignedDate, $excludeId = null)
    {
        $query = JobAssignment::where('worker_id', $workerId)->where('assigned_date', $assignedDate);
        if ($excludeId) $query->where('id', '!=', $excludeId);
        return $query->exists();
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

        JobAssignment::create($request->only(['service_job_id', 'worker_id', 'assigned_date', 'note']));
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

        JobAssignment::findOrFail($id)->update($request->only(['service_job_id', 'worker_id', 'assigned_date', 'note']));
        return response()->json(['message' => 'Assignment updated successfully.']);
    }

    public function assignmentDestroy($id)
    {
        JobAssignment::findOrFail($id)->delete();
        return response()->json(['message' => 'Assignment deleted successfully.']);
    }

    public function userHome()
    {
        return 'user';
    }
}