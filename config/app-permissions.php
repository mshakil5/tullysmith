<?php

return [
    'permissions' => [
        // Employee
        'employee.index' => 'View Employees',
        'employee.store' => 'Create Employee',
        'employee.edit' => 'Edit Employee',
        'employee.update' => 'Update Employee',
        'employee.delete' => 'Delete Employee',
        'employee.toggleStatus' => 'Toggle Employee Status',

        // Client
        'client.index' => 'View Clients',
        'client.store' => 'Create Client',
        'client.edit' => 'Edit Client',
        'client.update' => 'Update Client',
        'client.delete' => 'Delete Client',
        'client.toggleStatus' => 'Toggle Client Status',

        // Project
        'project.index' => 'View Projects',
        'project.store' => 'Create Project',
        'project.edit' => 'Edit Project',
        'project.update' => 'Update Project',
        'project.delete' => 'Delete Project',

        // Service Job
        'serviceJob.index' => 'View Service Jobs',
        'serviceJob.store' => 'Create Service Job',
        'serviceJob.edit' => 'Edit Service Job',
        'serviceJob.update' => 'Update Service Job',
        'serviceJob.delete' => 'Delete Service Job',
        'serviceJob.show' => 'View Service Job Details',

        // Note
        'note.store' => 'Add Note',
        'note.getNotes' => 'View Notes',
        'note.destroy' => 'Delete Note',

        // Document
        'document.store' => 'Upload Document',
        'document.getDocuments' => 'View Documents',
        'document.destroy' => 'Delete Document',

        // Checklist
        'checklist.index' => 'View Checklists',
        'checklist.store' => 'Create Checklist',
        'checklist.edit' => 'Edit Checklist',
        'checklist.update' => 'Update Checklist',
        'checklist.delete' => 'Delete Checklist',
        'checklist.toggleStatus' => 'Toggle Checklist Status',
        'checklist.active.list' => 'View Active Checklist List',
        'checklist.items' => 'View Checklist Items',
        'checklist.service-job' => 'View Service Job Checklists',
        'checklist.service-job.store' => 'Assign Checklist to Service Job',
        'checklist.service-job.destroy' => 'Remove Checklist from Service Job',

        // Roles
        'role.index' => 'View Roles',
        'role.store' => 'Create Role',
        'role.edit' => 'Edit Role',
        'role.update' => 'Update Role',
        'role.delete' => 'Delete Role',
        'role.permissions' => 'View Role Permissions',

        // Contacts
        'contacts.index' => 'View Contacts',
        'contacts.show' => 'View Contact Details',
        'contacts.delete' => 'Delete Contact',
        'contacts.toggleStatus' => 'Toggle Contact Status',

        // Company/Admin
        'admin.companyDetails' => 'Edit Company Details',
        'admin.seo-meta' => 'Edit SEO Meta',
        'admin.aboutUs' => 'Edit About Us',
        'admin.privacy-policy' => 'Edit Privacy Policy',
        'admin.terms-and-conditions' => 'Edit Terms & Conditions',
        'admin.mail-body' => 'Edit Mail Body',
        'admin.home-footer' => 'Edit Home Footer',
        'admin.copyright' => 'Edit Copyright',

        // Dashboard
        'admin.dashboard' => 'View Dashboard',
    ]
];