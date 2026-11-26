<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\AppIncident;
use Illuminate\Http\Request;
class DiagnosticsController extends Controller {
 public function store(Request $request) {
  $data=$request->validate(['operation'=>'required|in:dashboard,notifications,attendance,gps,postcode,app_feedback','kind'=>'required|in:timeout,network,error,gps_error,invalid_postcode,geofence_rejected','platform'=>'nullable|in:ios,android,other','version'=>'nullable|string|max:40','reference'=>'nullable|regex:/^[a-zA-Z0-9-]{8,64}$/D','distance_metres'=>'nullable|integer|min:0|max:21000000','accuracy_metres'=>'nullable|integer|min:0|max:21000000','duration_ms'=>'nullable|integer|min:0|max:600000']);
  AppIncident::record($data + ['source'=>'mobile','user_id'=>$request->user()->id]);
  return response()->noContent();
 }
}
