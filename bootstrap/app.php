<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([

        ]);
        $middleware->api(prepend: [\App\Http\Middleware\MonitorApi::class]);
        $middleware->alias([
            'is_admin' => IsAdmin::class,
            'is_client' => IsClient::class,
            'permission.check' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        \App\Console\Commands\SyncAppPermissions::class,
        \App\Console\Commands\AttendanceReminders::class,
    ])
    ->create();