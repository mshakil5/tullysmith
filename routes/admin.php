<?php

use App\Http\Controllers\Admin\ChecklistController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CompanyDetailsController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ServiceJobChecklistController;
use App\Http\Controllers\Admin\ServiceJobController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/', 'middleware' => ['auth', 'is_admin']], function () {
    Route::get('/dashboard', [HomeController::class, 'adminHome'])->name('admin.dashboard');

    // Employee
    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit'])->name('employee.edit');
    Route::post('/employee-update', [EmployeeController::class, 'update'])->name('employee.update');
    Route::delete('/employee/{id}', [EmployeeController::class, 'destroy'])->name('employee.delete');
    Route::post('/employee-status', [EmployeeController::class, 'toggleStatus'])->name('employee.toggleStatus');

    // Client
    Route::get('/client', [ClientController::class, 'index'])->name('client.index');
    Route::post('/client', [ClientController::class, 'store'])->name('client.store');
    Route::get('/client/{id}/edit', [ClientController::class, 'edit'])->name('client.edit');
    Route::post('/client-update', [ClientController::class, 'update'])->name('client.update');
    Route::delete('/client/{id}', [ClientController::class, 'destroy'])->name('client.delete');
    Route::post('/client-status', [ClientController::class, 'toggleStatus'])->name('client.toggleStatus');

    // Project
    Route::get('/project', [ProjectController::class, 'index'])->name('project.index');
    Route::post('/project', [ProjectController::class, 'store'])->name('project.store');
    Route::get('/project/{id}/edit', [ProjectController::class, 'edit'])->name('project.edit');
    Route::post('/project-update', [ProjectController::class, 'update'])->name('project.update');
    Route::delete('/project/{id}', [ProjectController::class, 'destroy'])->name('project.destroy');

    // Service Job
    Route::get('/service-job', [ServiceJobController::class, 'index'])->name('serviceJob.index');
    Route::post('/service-job', [ServiceJobController::class, 'store'])->name('serviceJob.store');
    Route::get('/service-job/{id}/edit', [ServiceJobController::class, 'edit'])->name('serviceJob.edit');
    Route::post('/service-job-update', [ServiceJobController::class, 'update'])->name('serviceJob.update');
    Route::delete('/service-job/{id}', [ServiceJobController::class, 'destroy'])->name('serviceJob.delete');
    Route::get('/service-job/{id}', [ServiceJobController::class, 'show'])->name('serviceJob.show');

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
    Route::get('/service-job/{id}/checklists', [ServiceJobChecklistController::class, 'getChecklists'])->name('service-job.checklists');
    Route::post('/service-job/checklist', [ServiceJobChecklistController::class, 'store'])->name('service-job.checklist.store');
    Route::delete('/service-job/checklist/{id}', [ServiceJobChecklistController::class, 'destroy'])->name('service-job.checklist.destroy');

    //Checklist
    Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist.index');
    Route::post('/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
    Route::get('/checklist/{id}/edit', [ChecklistController::class, 'edit'])->name('checklist.edit');
    Route::post('/checklist-update', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::delete('/checklist/{id}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
    Route::post('/checklist-status', [ChecklistController::class, 'toggleStatus'])->name('checklist.toggleStatus');

    // Contact
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{id}', [ContactController::class, 'show'])->name('contacts.show');
    Route::delete('/contacts/{id}/delete', [ContactController::class, 'destroy'])->name('contacts.delete');
    Route::post('/contacts/toggle-status', [ContactController::class, 'toggleStatus'])->name('contacts.toggleStatus');

    // Company
    Route::get('/company-details', [CompanyDetailsController::class, 'index'])->name('admin.companyDetails');
    Route::post('/company-details', [CompanyDetailsController::class, 'update'])->name('admin.companyDetails');

    Route::get('/company/seo-meta', [CompanyDetailsController::class, 'seoMeta'])->name('admin.company.seo-meta');
    Route::post('/company/seo-meta/update', [CompanyDetailsController::class, 'seoMetaUpdate'])->name('admin.company.seo-meta.update');

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