<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "You don't have permission to perform this action"
                ], 403);
            } else {
                abort(403, "You don't have permission to perform this action");
            }
        }

        $routeName = Route::currentRouteName();
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        if (!in_array($routeName, $userPermissions)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "You don't have permission to perform this action",
                    'permission' => $routeName
                ], 403);
            } else {
                abort(403, "You don't have permission to perform this action");
            }
        }

        return $next($request);
    }
}