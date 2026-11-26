<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
class AttendanceSettingController extends Controller {
 public function index(Request $r){abort_unless($r->user()->hasRole('Super Admin'),403);return view('admin.pages.attendance-settings',['s'=>AttendanceSetting::values()]);}
 public function update(Request $r){abort_unless($r->user()->hasRole('Super Admin'),403);
  $data=$r->validate(['start'=>'nullable|required_with:finish|date_format:H:i','finish'=>'nullable|required_with:start|date_format:H:i|different:start','days'=>'nullable|array','days.*'=>'integer|between:1,7|distinct','before'=>'required|integer|in:0,5,10,15,30','repeat'=>'required|integer|in:15,30,45,60','max_hours'=>'required|integer|between:8,24','clock_in'=>'nullable|boolean','clock_out'=>'nullable|boolean']);
  $data['days']=array_map('intval',$data['days']??[]);$data['clock_in']=$r->boolean('clock_in');$data['clock_out']=$r->boolean('clock_out');
  AttendanceSetting::updateOrCreate(['id'=>1],['settings'=>$data]);return back()->with('success','Attendance settings saved.');
 }
}
