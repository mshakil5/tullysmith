<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MonitoringController extends Controller {
 public function index(Request $request) {
  abort_unless($request->user()->hasRole('Super Admin'),403);
  $base=DB::table('app_incidents')->where('app_incidents.created_at','>=',now()->subDay());
  $counts=(clone $base)->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total','kind');
  $query=DB::table('app_incidents')->leftJoin('users','users.id','=','app_incidents.user_id')->select('app_incidents.*','users.name as employee');
  if(in_array($request->input('source'),['mobile','server'],true)) $query->where('source',$request->input('source'));
  if($request->filled('user_id')) $query->where('app_incidents.user_id',(int)$request->input('user_id'));
  $events=$query->orderByDesc('app_incidents.id')->paginate(50)->withQueryString();
  return view('admin.pages.monitoring',compact('counts','events'));
 }
}
