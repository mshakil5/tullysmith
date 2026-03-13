<?php

use App\Http\Controllers\Api\AdminTimeController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthContoller;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\TimeController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthContoller::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/dashboard', [AuthContoller::class, 'dashboard']);

    Route::prefix('employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/status', [EmployeeController::class, 'toggleStatus']);
    });
    
    Route::prefix('checklist')->group(function () {
        Route::get('/', [ChecklistController::class, 'index']);
        Route::post('/', [ChecklistController::class, 'store']);
        Route::post('/status', [ChecklistController::class, 'toggleStatus']);
        Route::get('/{id}', [ChecklistController::class, 'show']);
        Route::put('/{id}', [ChecklistController::class, 'update']);
        Route::delete('/{id}', [ChecklistController::class, 'destroy']);
    });

    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
        Route::post('/status', [ClientController::class, 'toggleStatus']);
    });

    Route::prefix('jobs')->group(function () {
        Route::get('/', [JobController::class, 'index']);
        Route::post('/', [JobController::class, 'store']);
        Route::get('/{id}', [JobController::class, 'show']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        
        Route::get('/{id}/detail', [JobController::class, 'detail']);
        Route::post('/{id}/notes', [JobController::class, 'storeNote']);
        Route::delete('/{id}/notes/{noteId}', [JobController::class, 'deleteNote']);
        Route::post('/{id}/documents', [JobController::class, 'storeDocument']);
        Route::delete('/{id}/documents/{docId}', [JobController::class, 'deleteDocument']);
        Route::post('/{id}/checklists', [JobController::class, 'assignChecklist']);
        Route::delete('/{id}/checklists/{assignmentId}', [JobController::class, 'removeChecklist']);
        Route::post('/checklists/{assignmentId}/answers', [JobController::class, 'saveAnswers']);
    });

    Route::prefix('time')->name('api.time.')->group(function () {
        Route::get('/', [TimeController::class, 'index']);
        Route::post('/clock-in', [TimeController::class, 'clockIn']);
        Route::post('/clock-out', [TimeController::class, 'clockOut']);
        Route::get('/checklist-questions', [TimeController::class, 'getClockChecklists']);
        Route::post('/save-checklist-answers', [TimeController::class, 'saveClockChecklistAnswers']);
        Route::get('/timesheet', [TimeController::class, 'timesheet']);
    });

    Route::prefix('admin/time')->name('api.admin.time.')->group(function () {
        Route::get('/workers', [AdminTimeController::class, 'workers']);
        Route::get('/worker-data', [AdminTimeController::class, 'workerData']);
        Route::post('/manual-clock-in', [AdminTimeController::class, 'manualClockIn']);
        Route::post('/clock-out', [AdminTimeController::class, 'clockOut']);
    });

    Route::prefix('approvals')->name('api.approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::get('/{type}/{id}', [ApprovalController::class, 'show']);
        Route::post('/{type}/{id}/action', [ApprovalController::class, 'action']);
    });

    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::put('/{id}', [CalendarController::class, 'update'])->name('update');
        Route::delete('/{id}', [CalendarController::class, 'destroy'])->name('delete');
    });

});