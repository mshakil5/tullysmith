<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RequireAttendanceAdmin {
 public function handle(Request $request, Closure $next) {
  abort_unless($request->user() && $request->user()->hasAnyRole(['Super Admin','Line Manager']),403);
  return $next($request);
 }
}
