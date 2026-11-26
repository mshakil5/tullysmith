<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\AttendanceExceptions;
use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AttendanceExceptionController extends Controller {
 public function index(Request $r){abort_unless($r->user()->hasRole('Super Admin'),403);$logs=AttendanceExceptions::query()->with(['worker','job'])->orderByDesc('clock_in_at')->paginate(30);return view('admin.pages.attendance-exceptions',compact('logs'));}
 public function resolve(Request $r,$id){abort_unless($r->user()->hasRole('Super Admin'),403);$data=$r->validate(['finish'=>'required|date|before_or_equal:now','reason'=>'required|string|max:1000']);
 DB::transaction(function() use($r,$id,$data){$log=TimeLog::whereKey($id)->lockForUpdate()->firstOrFail();abort_unless(AttendanceExceptions::query()->whereKey($id)->exists(),409);$finish=Carbon::parse($data['finish']);abort_if($finish->lte($log->clock_in_at),422,'Finish must follow clock-in');$before=$log->only(['clock_in_at','clock_out_at','total_hours','status']);$log->update(['clock_out_at'=>$finish,'total_hours'=>round($log->clock_in_at->diffInSeconds($finish)/3600,2),'exception_reviewed_at'=>now()]);DB::table('attendance_exception_audits')->insert(['time_log_id'=>$log->id,'reviewer_id'=>$r->user()->id,'reason'=>$data['reason'],'before'=>json_encode($before),'after'=>json_encode($log->only(['clock_in_at','clock_out_at','total_hours','status'])),'created_at'=>now()]);});return back()->with('success','Attendance reviewed and corrected.');}
}
