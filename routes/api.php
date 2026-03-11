<?php

use App\Http\Controllers\Api\AuthContoller;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceJobChecklistController;
use App\Http\Controllers\Api\ServiceJobController;
use App\Http\Controllers\Api\TimeController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthContoller::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/dashboard', [AuthContoller::class, 'dashboard']);

    Route::prefix('time')->name('api.time.')->group(function () {
        Route::get('/', [TimeController::class, 'index'])->name('index');
        Route::post('/clock-in', [TimeController::class, 'clockIn'])->name('clockIn');
        Route::post('/clock-out', [TimeController::class, 'clockOut'])->name('clockOut');
        Route::get('/checklist-questions', [TimeController::class, 'getClockChecklists'])->name('checklistQuestions');
        Route::post('/save-checklist-answers', [TimeController::class, 'saveClockChecklistAnswers'])->name('saveClockChecklistAnswers');
        Route::get('/timesheet', [TimeController::class, 'timesheet'])->name('timesheet');
    });

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

    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
        Route::post('/status', [ClientController::class, 'toggleStatus']);
    });

    Route::prefix('project')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::post('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
    });

    Route::prefix('job')->group(function () {
        Route::get('/', [ServiceJobController::class, 'index']);
        Route::post('/', [ServiceJobController::class, 'store']);
        Route::get('/{id}', [ServiceJobController::class, 'show']);
        Route::post('/{id}', [ServiceJobController::class, 'update']);
        Route::delete('/{id}', [ServiceJobController::class, 'destroy']);
    });

    Route::prefix('note')->group(function () {
        Route::post('/', [NoteController::class, 'store']);
        Route::get('/service-job/{jobId}', [NoteController::class, 'getNotes']);
        Route::delete('/{id}', [NoteController::class, 'destroy']);
    });

    Route::prefix('document')->group(function () {
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/service-job/{jobId}', [DocumentController::class, 'getDocuments']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
    });

    Route::prefix('service-job')->group(function () {
        Route::get('/{id}/checklists', [ServiceJobChecklistController::class, 'getChecklists']);
        Route::post('/checklist', [ServiceJobChecklistController::class, 'store']);
        Route::delete('/checklist/{id}', [ServiceJobChecklistController::class, 'destroy']);
    });

});