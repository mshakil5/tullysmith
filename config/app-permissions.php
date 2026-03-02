<?php

return [
    'permissions' => [

        // Employee
        'employee.index',
        'employee.store',
        'employee.edit',
        'employee.update',
        'employee.delete',
        'employee.toggleStatus',

        // Client
        'client.index',
        'client.store',
        'client.edit',
        'client.update',
        'client.delete',
        'client.toggleStatus',

        // Service Job
        'serviceJob.index',
        'serviceJob.store',
        'serviceJob.edit',
        'serviceJob.update',
        'serviceJob.delete',
        'serviceJob.show',

        // Job Assignment
        'jobAssignment.index',
        'jobAssignment.data',
        'jobAssignment.store',
        'jobAssignment.update',
        'jobAssignment.delete',

        // Note
        'note.store',
        'note.getNotes',
        'note.destroy',

        // Document
        'document.store',
        'document.getDocuments',
        'document.destroy',

        // Checklist
        'checklist.index',
        'checklist.store',
        'checklist.edit',
        'checklist.update',
        'checklist.delete',
        'checklist.toggleStatus',
        'checklist.active.list',
        'checklist.items',
        'checklist.service-job',
        'checklist.service-job.store',
        'checklist.service-job.destroy',
        'checklist.answer',

        // Roles
        'role.index',
        'role.store',
        'role.edit',
        'role.update',
        'role.delete',
        'role.permissions',

        // Approvals
        'approvals.index',
        'approvals.show',
        'approvals.action',
        
        // Time
        'time.index',
        'time.clockIn',
        'time.clockOut',
        'time.stats',
        'time.timesheet',
        'time.export',
        'time.checklistQuestions',
        'time.saveClockChecklistAnswers',

        // Contacts
        // 'contacts.index',
        // 'contacts.show',
        // 'contacts.delete',
        // 'contacts.toggleStatus',

        // Company/Admin
        // 'admin.companyDetails',
        // 'admin.seo-meta',
        // 'admin.aboutUs',
        // 'admin.privacy-policy',
        // 'admin.terms-and-conditions',
        // 'admin.mail-body',
        // 'admin.home-footer',
        // 'admin.copyright',

        // Dashboard
        'admin.dashboard',
    ]
];