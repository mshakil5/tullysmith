<?php
namespace App\Console\Commands;
use App\Models\AttendanceSetting;
use App\Models\JobAssignment;
use App\Models\TimeLog;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
class AttendanceReminders extends Command {
 protected $signature='attendance:remind';
 protected $description='Send due attendance reminders for assigned work';
 public function handle(): int {
  $s=AttendanceSetting::values();if(!$s['clock_in']&&!$s['clock_out'])return self::SUCCESS;
  $now=CarbonImmutable::now('Europe/London');
  JobAssignment::query()->whereBetween('assigned_date',[$now->subDay()->toDateString(),$now->addDay()->toDateString()])->whereHas('worker',fn($q)=>$q->active())->whereHas('job',fn($q)=>$q->where('status','active'))->with('job')->chunkById(100,function($assignments)use($s,$now){
   foreach($assignments as $a){
    $date=CarbonImmutable::parse($a->assigned_date,'Europe/London');
    $fallback=in_array($date->dayOfWeekIso,$s['days']);
    $start=$a->start_time ?: ($fallback?$s['start']:null);$end=$a->end_time ?: ($fallback?$s['finish']:null);
    if(!$start||!$end||substr($start,0,5)===substr($end,0,5))continue;
    $start=CarbonImmutable::parse($date->toDateString().' '.$start,'Europe/London');$end=CarbonImmutable::parse($date->toDateString().' '.$end,'Europe/London');if($end->lte($start))$end=$end->addDay();
    $events=[];if($s['clock_in'])$events['clock_in']=[$start->subMinutes($s['before']),'Clock-in reminder','Please remember to clock in when you arrive at your assigned job.'];
    if($s['clock_out']){$events['clock_out']=[$end,'Clock-out reminder','Please remember to clock out when you finish work.'];$events['repeat']=[$end->addMinutes($s['repeat']),'Still clocked in','If you have finished work, please clock out.'];}
    foreach($events as $kind=>[$due,$title,$body]){
     if($now->lt($due)||$now->gte($due->addMinutes(5)))continue;
     $logs=TimeLog::where('worker_id',$a->worker_id)->where('job_assignment_id',$a->id)->whereNotNull('clock_in_at');
     if($kind==='clock_in' ? $logs->exists() : !$logs->whereNull('clock_out_at')->exists())continue;
     if(!DB::table('attendance_reminder_events')->insertOrIgnore(['assignment_id'=>$a->id,'kind'=>$kind,'created_at'=>now()]))continue;
     try{app(NotificationService::class)->sendToUser($a->worker_id,$title,$body,'attendance_reminder');}catch(\Throwable $e){report($e);}
    }
   }
  });return self::SUCCESS;
 }
}
