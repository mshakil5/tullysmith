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

        $myAssignments = JobAssignment::with('job:id,job_title,job_id,address_line1,address_line2,city,postcode,status,priority,client_id', 'job.client:id,name')
        ->where('worker_id', auth()->id())
        ->get()
        ->map(function ($a) {
            return [
                'id'             => $a->id,
                'title'          => $a->job->job_title,
                'start'          => $a->assigned_date . ($a->start_time ? 'T' . $a->start_time : ''),
                'end'            => $a->assigned_date . ($a->end_time ? 'T' . $a->end_time : ''),
                'assigned_date'  => $a->assigned_date,
                'job_title'      => $a->job->job_title,
                'job_id'         => $a->job->job_id,
                'client_name'    => $a->job->client->name ?? '-',
                'address'        => collect([$a->job->address_line1, $a->job->address_line2, $a->job->city, $a->job->postcode])->filter()->implode(', '),
                'status'         => $a->job->status,
                'priority'       => $a->job->priority,
                'start_time'     => $a->start_time,
                'end_time'       => $a->end_time,
                'note'           => $a->note,
                'service_job_id' => $a->service_job_id,
                'backgroundColor'=> '#16a34a',
                'borderColor'    => '#15803d',
            ];
        });

        return view('admin.pages.dashboard', compact(
            'totalStaff','activeJobs','pendingJobs','todaysAssignments','assignments','myAssignments'
        ));
    }

  public function userHome()
  {
    return 'user';
  }
}
