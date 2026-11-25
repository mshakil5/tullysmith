<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class AppIncident {
 public static function record(array $data): void {
  try { DB::table('app_incidents')->insert($data + ['created_at'=>now()]); }
  catch (\Throwable $e) { Log::warning('App incident storage unavailable'); }
 }
}
