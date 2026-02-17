<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
  public function handle(Request $request, Closure $next): Response
  {
    if (auth()->check() && in_array(auth()->user()->user_type, [1, 2, 3])) {
      return $next($request);
    }

    return redirect()->route('login');
  }
}