<?php

use App\Http\Controllers\Api\AdminTimeController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthContoller;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TimeController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthContoller::class, 'login']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/dashboard', [AuthContoller::class, 'dashboard']);
    Route::get('/dashboard/assignment-data', [AuthContoller::class, 'assignmentData']);
    Route::post('/dashboard/assignment', [AuthContoller::class, 'assignmentStore']);
    Route::post('/dashboard/assignment/{id}/update', [AuthContoller::class, 'assignmentUpdate']);
    Route::delete('/dashboard/assignment/{id}', [AuthContoller::class, 'assignmentDestroy']);

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

    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index']);
        Route::post('/', [AnnouncementController::class, 'store']);
        Route::get('/{id}', [AnnouncementController::class, 'show']);
        Route::put('/{id}', [AnnouncementController::class, 'update']);
        Route::delete('/{id}', [AnnouncementController::class, 'destroy']);
        Route::post('/status', [AnnouncementController::class, 'toggleStatus']);
    });

    Route::prefix('jobs')->group(function () {
        Route::get('/', [JobController::class, 'index']);
        Route::post('/', [JobController::class, 'store']);
        Route::get('/archived', [JobController::class, 'archived']);
        Route::get('/next-id', [JobController::class, 'nextJobId']);
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

    Route::prefix('expenses')->group(function () {
        Route::get('/', [JobController::class, 'getAllExpenses']);
        Route::post('/', [JobController::class, 'storeExpense']);
        Route::get('/{id}', [JobController::class, 'getExpense']);
        Route::put('/{id}', [JobController::class, 'updateExpense']);
        Route::delete('/{id}', [JobController::class, 'deleteExpense']);
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

    Route::prefix('reports')->group(function () {
        Route::get('/filter-options',   [ReportController::class, 'filterOptions']);
        Route::get('/time/pdf',         [ReportController::class, 'timePdf']);
        Route::get('/expense/pdf',      [ReportController::class, 'expensePdf']);
        Route::get('/checklist/pdf',    [ReportController::class, 'checklistPdf']);
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/fcm-token', [NotificationController::class, 'saveFcmToken']);
    Route::delete('/fcm-token', [NotificationController::class, 'deleteFcmToken']);

});