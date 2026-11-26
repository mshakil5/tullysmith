<?php
namespace App\Services;
use App\Models\TimeLog;
class AttendanceExceptions {
 public static function query() {
  return TimeLog::query()->whereNotNull('clock_in_at')->whereNull('exception_reviewed_at')->where(function($q){
   $q->where(function($q){$q->whereNull('clock_out_at')->where(function($q){$q->where('clock_in_at','<',now()->startOfDay())->orWhere('clock_in_at','<=',now()->subHours(12));});})->orWhere('total_hours','>=',12)->orWhere(function($q){$q->whereNotNull('clock_out_at')->whereRaw('DATE(clock_out_at) > DATE(clock_in_at)');});
  });
 }
 public static function stale(TimeLog $log): bool {return $log->clock_in_at->toDateString() !== now()->toDateString() || $log->clock_in_at->lte(now()->subHours(12));}
}
