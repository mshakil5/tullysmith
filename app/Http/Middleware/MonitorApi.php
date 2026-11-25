<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AppIncident;
class MonitorApi {
 public function handle(Request $request, Closure $next) {
  $start = hrtime(true); $status = 500; $response = null;
  $reference = $request->header('X-Request-ID');
  if (!is_string($reference) || !preg_match('/^[a-zA-Z0-9-]{8,64}$/D', $reference)) $reference = (string) Str::uuid();
  try { $response = $next($request); $status = $response->getStatusCode(); $response->headers->set('X-Request-ID',$reference); return $response; }
  catch (\Throwable $e) {
   $status = $e instanceof \Illuminate\Validation\ValidationException ? 422 : ($e instanceof \Illuminate\Auth\AuthenticationException ? 401 : ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500));
   throw $e;
  } finally {
   $ms = (int)((hrtime(true)-$start)/1000000);
   if (!$request->is('api/diagnostics') && !($request->is('api/login') && $status === 401) && ($status >= 400 || $ms >= 2000)) {
    try { AppIncident::record(['source'=>'server','operation'=>substr($request->method().' '.($request->route()?->uri() ?? 'unmatched'),0,120),'kind'=>$status>=400?'http_error':'slow_request','status'=>$status,'duration_ms'=>$ms,'reference'=>$reference,'user_id'=>auth('api')->id()]); } catch (\Throwable $ignored) {}
   }
  }
 }
}
