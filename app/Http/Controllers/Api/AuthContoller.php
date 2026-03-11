<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\ServiceJob;
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
            'role' => $role,
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 200);
    }

    public function dashboard()
    {
        $workerId = auth()->id();
        $today = now()->toDateString();

        $activeJobs = ServiceJob::where('status', 'active')->count();
        $pendingJobs = ServiceJob::where('status', 'pending')->count();

        $assignments = JobAssignment::with([
            'job:id,job_title,job_id,address_line1,address_line2,city,postcode,status,priority,client_id',
            'job.client:id,name',
            'worker:id,name',
        ])
        ->where('worker_id', $workerId)
        ->get()
        ->map(function ($a) {
            return [
                'id' => $a->id,
                'assigned_date' => $a->assigned_date,
                'worker_name' => $a->worker->name ?? '-',
                'job_title' => $a->job->job_title ?? '',
                'job_id' => $a->job->job_id ?? '',
                'client_name' => $a->job->client->name ?? '-',
                'address' => collect([
                    $a->job->address_line1,
                    $a->job->address_line2,
                    $a->job->city,
                    $a->job->postcode
                ])->filter()->implode(', '),
                'status' => $a->job->status ?? '',
                'priority' => $a->job->priority ?? '',
                'start_time' => $a->start_time,
                'end_time' => $a->end_time,
                'note' => $a->note,
                'service_job_id' => $a->service_job_id,
            ];
        });

        $todayJobs = $assignments->where('assigned_date', $today)->count();

        return response()->json([
            'today_jobs' => $todayJobs,
            'active_jobs' => $activeJobs,
            'pending_jobs' => $pendingJobs,
            'assignments' => $assignments,
        ]);
    }
}
