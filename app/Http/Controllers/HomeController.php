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
        $workerId = auth()->id();
        $today    = now()->toDateString();

        $totalWorker        = User::byRole('Worker')->count();
        $activeJobs        = ServiceJob::where('status', 'active')->count();
        $pendingJobs       = ServiceJob::where('status', 'pending')->count();
        $todaysAssignments = JobAssignment::where('assigned_date', $today)->count();

        $mapAssignment = function ($a) {
            return [
                'id'              => $a->id,
                'title'           => $a->worker->name . ' — ' . $a->job->job_title,
                'start'           => $a->assigned_date . ($a->start_time ? 'T' . $a->start_time : ''),
                'end'             => $a->assigned_date . ($a->end_time ? 'T' . $a->end_time : ''),
                'assigned_date'   => $a->assigned_date,
                'worker_name'     => $a->worker->name ?? '-',
                'job_title'       => $a->job->job_title,
                'job_id'          => $a->job->job_id,
                'client_name'     => $a->job->client->name ?? '-',
                'address'         => collect([$a->job->address_line1, $a->job->address_line2, $a->job->city, $a->job->postcode])->filter()->implode(', '),
                'status'          => $a->job->status,
                'priority'        => $a->job->priority,
                'start_time'      => $a->start_time,
                'end_time'        => $a->end_time,
                'note'            => $a->note,
                'service_job_id'  => $a->service_job_id,
                'worker_id'       => $a->worker_id,
            ];
        };

        $baseQuery = JobAssignment::with([
            'job:id,job_title,job_id,address_line1,address_line2,city,postcode,status,priority,client_id',
            'job.client:id,name',
            'worker:id,name',
        ]);

        $assignments = $baseQuery->get()->map(function ($a) use ($mapAssignment) {
            return array_merge($mapAssignment($a), [
                'textColor'       => '#ffffff',
                'backgroundColor' => '#405189',
                'borderColor'     => '#2c3e75',
            ]);
        });

        $myAssignments = (clone $baseQuery)->where('worker_id', $workerId)->get()->map(function ($a) use ($mapAssignment) {
            return array_merge($mapAssignment($a), [
                'title'           => $a->job->job_title,
                'backgroundColor' => '#16a34a',
                'borderColor'     => '#15803d',
                'textColor'       => '#ffffff',
            ]);
        });

        return view('admin.pages.dashboard', compact(
            'totalWorker', 'activeJobs', 'pendingJobs', 'todaysAssignments', 'assignments', 'myAssignments'
        ));
    }

  public function userHome()
  {
    return 'user';
  }
}
