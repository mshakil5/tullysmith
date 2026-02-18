<?php

use App\Http\Controllers\Api\AuthContoller;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthContoller::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {

    Route::prefix('employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::post('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
    });

    Route::prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    Route::prefix('checklist')->group(function () {
        Route::get('/', [ChecklistController::class, 'index']);
        Route::post('/', [ChecklistController::class, 'store']);
        Route::get('/active', [ChecklistController::class, 'activeList']);
        Route::get('/{id}', [ChecklistController::class, 'show']);
        Route::post('/{id}', [ChecklistController::class, 'update']);
        Route::delete('/{id}', [ChecklistController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [ChecklistController::class, 'toggleStatus']);
        Route::get('/{id}/items', [ChecklistController::class, 'items']);
    });

    Route::prefix('client')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::post('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [ClientController::class, 'toggleStatus']);
    });

});