<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\ChecklistController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CompanyDetailsController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\JobAssignmentController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceJobChecklistController;
use App\Http\Controllers\Admin\ServiceJobController;
use App\Http\Controllers\Admin\TimeController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/', 'middleware' => ['auth', 'is_admin', 'permission.check']], function () {
    Route::get('/dashboard', [HomeController::class, 'adminHome'])->name('admin.dashboard');

    // Employee
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::post('/update', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('delete');
        Route::post('/status', [EmployeeController::class, 'toggleStatus'])->name('toggleStatus');
    });

    // Client
    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::post('/update', [ClientController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClientController::class, 'destroy'])->name('delete');
        Route::post('/status', [ClientController::class, 'toggleStatus'])->name('toggleStatus');
    });

    // Service Job
    Route::prefix('service-job')->name('serviceJob.')->group(function () {
        Route::get('/', [ServiceJobController::class, 'index'])->name('index');
        Route::post('/', [ServiceJobController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ServiceJobController::class, 'edit'])->name('edit');
        Route::post('/update', [ServiceJobController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServiceJobController::class, 'destroy'])->name('delete');
        Route::get('/{id}', [ServiceJobController::class, 'show'])->name('show');
    });

    // Time
    Route::prefix('time')->name('time.')->group(function () {
        Route::get('/',            [TimeController::class, 'index'])->name('index');
        Route::post('/clock-in',   [TimeController::class, 'clockIn'])->name('clockIn');
        Route::post('/clock-out',  [TimeController::class, 'clockOut'])->name('clockOut');
        Route::get('/stats',       [TimeController::class, 'stats'])->name('stats');
        Route::get('/timesheet',   [TimeController::class, 'timesheet'])->name('timesheet');
        Route::get('/export',      [TimeController::class, 'exportTimesheet'])->name('export');
    });

    Route::prefix('job-assignment')->name('jobAssignment.')->group(function () {
        Route::get('/', [JobAssignmentController::class, 'index'])->name('index');
        Route::get('/data', [JobAssignmentController::class, 'data'])->name('data');
        Route::post('/', [JobAssignmentController::class, 'store'])->name('store');
        Route::post('/{id}/update', [JobAssignmentController::class, 'update'])->name('update');
        Route::delete('/{id}', [JobAssignmentController::class, 'destroy'])->name('delete');
    });

    // Note
    Route::post('/note', [NoteController::class, 'store'])->name('note.store');
    Route::get('/service-job/{jobId}/notes', [NoteController::class, 'getNotes'])->name('note.getNotes');
    Route::delete('/note/{id}', [NoteController::class, 'destroy'])->name('note.destroy');

    // Document
    Route::post('/document', [DocumentController::class, 'store'])->name('document.store');
    Route::get('/service-job/{jobId}/documents', [DocumentController::class, 'getDocuments'])->name('document.getDocuments');
    Route::delete('/document/{id}', [DocumentController::class, 'destroy'])->name('document.destroy');

    // Checklist Under Service Job
    Route::get('/checklist/active/list', [ChecklistController::class, 'getActiveList'])->name('checklist.active.list');
    Route::get('/checklist/{id}/items', [ChecklistController::class, 'getItems'])->name('checklist.items');
    Route::get('/service-job/{id}/checklists', [ServiceJobChecklistController::class, 'getChecklists'])->name('checklist.service-job');
    Route::post('/service-job/checklist', [ServiceJobChecklistController::class, 'store'])->name('checklist.service-job.store');
    Route::delete('/service-job/checklist/{id}', [ServiceJobChecklistController::class, 'destroy'])->name('checklist.service-job.destroy');

    //Checklist
    Route::prefix('checklist')->name('checklist.')->group(function () {
        Route::get('/', [ChecklistController::class, 'index'])->name('index');
        Route::post('/', [ChecklistController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ChecklistController::class, 'edit'])->name('edit');
        Route::post('/update', [ChecklistController::class, 'update'])->name('update');
        Route::delete('/{id}', [ChecklistController::class, 'destroy'])->name('delete');
        Route::post('/status', [ChecklistController::class, 'toggleStatus'])->name('toggleStatus');
    });

    // Roles
    Route::prefix('roles')->name('role.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::post('/update', [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoleController::class, 'delete'])->name('delete');
        Route::get('/permissions', [RoleController::class, 'permissions'])->name('permissions');
    });

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/{type}/{id}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('/approvals/{type}/{id}/action', [ApprovalController::class, 'action'])->name('approvals.action');

    // Contact
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{id}', [ContactController::class, 'show'])->name('contacts.show');
    Route::delete('/contacts/{id}/delete', [ContactController::class, 'destroy'])->name('contacts.delete');
    Route::post('/contacts/toggle-status', [ContactController::class, 'toggleStatus'])->name('contacts.toggleStatus');

    // Company
    Route::get('/company-details', [CompanyDetailsController::class, 'index'])->name('admin.companyDetails');
    Route::post('/company-details', [CompanyDetailsController::class, 'update'])->name('admin.companyDetails');

    Route::get('/company/seo-meta', [CompanyDetailsController::class, 'seoMeta'])->name('admin.seo-meta');
    Route::post('/company/seo-meta/update', [CompanyDetailsController::class, 'seoMetaUpdate'])->name('admin.seo-meta');

    Route::get('/about-us', [CompanyDetailsController::class, 'aboutUs'])->name('admin.aboutUs');
    Route::post('/about-us', [CompanyDetailsController::class, 'aboutUsUpdate'])->name('admin.aboutUs');

    Route::get('/privacy-policy', [CompanyDetailsController::class, 'privacyPolicy'])->name('admin.privacy-policy');
    Route::post('/privacy-policy', [CompanyDetailsController::class, 'privacyPolicyUpdate'])->name('admin.privacy-policy');

    Route::get('/terms-and-conditions', [CompanyDetailsController::class, 'termsAndConditions'])->name('admin.terms-and-conditions');
    Route::post('/terms-and-conditions', [CompanyDetailsController::class, 'termsAndConditionsUpdate'])->name('admin.terms-and-conditions');

    Route::get('/mail-body', [CompanyDetailsController::class, 'mailBody'])->name('admin.mail-body');
    Route::post('/mail-body', [CompanyDetailsController::class, 'mailBodyUpdate'])->name('admin.mail-body');

    Route::get('/home-footer', [CompanyDetailsController::class, 'homeFooter'])->name('admin.home-footer');
    Route::post('/home-footer', [CompanyDetailsController::class, 'homeFooterUpdate'])->name('admin.home-footer');

    Route::get('/copyright', [CompanyDetailsController::class, 'copyright'])->name('admin.copyright');
    Route::post('/copyright', [CompanyDetailsController::class, 'copyrightUpdate'])->name('admin.copyright');
});