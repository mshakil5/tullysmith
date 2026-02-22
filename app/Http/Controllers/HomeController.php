<?php

namespace App\Http\Controllers;

use App\Models\JobAssignment;
use App\Models\ServiceJob;
use App\Models\User;
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
        $totalStaff       = User::byRole('Worker')->count();
        $activeJobs       = ServiceJob::where('status','active')->count();
        $pendingJobs      = ServiceJob::where('status','pending')->count();
        $todaysAssignments= JobAssignment::where('assigned_date', date('Y-m-d'))->count();

        $assignments = JobAssignment::with('job:id,job_title,job_id','worker:id,name')
            ->get()
            ->map(function($a){
                return [
                    'id'             => $a->id,
                    'title'          => $a->worker->name.' — '.$a->job->job_title,
                    'start'          => $a->assigned_date.($a->start_time?'T'.$a->start_time:''),
                    'end'            => $a->assigned_date.($a->end_time?'T'.$a->end_time:''),
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

        return view('admin.pages.dashboard', compact(
            'totalStaff','activeJobs','pendingJobs','todaysAssignments','assignments'
        ));
    }

  public function userHome()
  {
    return 'user';
  }
}
