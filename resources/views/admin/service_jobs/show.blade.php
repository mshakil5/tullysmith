@extends('admin.pages.master')
@section('title', 'Job Details')
@section('content')

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>{{ $job->job_title }}</h2>
                    <span class="badge bg-{{ $job->status === 'draft' ? 'secondary' : ($job->status === 'active' ? 'success' : ($job->status === 'pending' ? 'warning' : 'primary')) }}">{{ ucfirst($job->status) }}</span>
                </div>
                <a href="{{ route('serviceJob.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Client</p>
                    <h6>{{ $job->client->name ?? '' }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Duration</p>
                    <h6>{{ $job->formattedStartDate() }} - {{ $job->formattedEndDate() }}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">Estimated Hours</p>
                    <h6>{{ $job->estimated_hours ?? 0 }} hrs</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="jobTabs">
                <li class="nav-item">
                    <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents" role="tab">Documents (<span id="documentsCount">0</span>)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="checklists-tab" data-bs-toggle="tab" href="#checklists" role="tab">Checklists (<span id="navChecklistsCount">0</span>)</a>
                </li>
                @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#assignments" role="tab">
                        Assignments <span class="badge bg-primary ms-1">{{ $job->assignments->count() }}</span>
                    </a>
                </li>
                @endhasanyrole
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="jobTabContent">
                
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Description</h5>
                            <p>{!! $job->description ?? '' !!}</p>

                            <h5 class="mt-4">Instructions</h5>
                            <p>{!! $job->instructions ?? '' !!}</p>

                            <h5 class="mt-4">Address</h5>
                            <p>
                                {{ collect([$job->address_line1, $job->address_line2, $job->city, $job->postcode])->filter()->implode(', ') ?: '' }}
                            </p>

                            <h5 class="mt-4">Daily Notes & Updates (<span id="notesCount">0</span>)</h5>
                            <div class="card">
                                <div class="card-body">
                                    <form id="noteForm">
                                        @csrf
                                        <input type="hidden" id="service_job_id" name="service_job_id" value="{{ $job->id }}">
                                        <div class="mb-3">
                                            <textarea class="form-control" id="noteText" name="note" rows="3" placeholder="Add a note for the team..."></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary btn-sm">Post</button>
                                        </div>
                                    </form>
                                    <div id="notesContainer" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
                                        <p class="text-muted text-center py-4">Loading notes...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Job Information</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-3">
                                            <span class="text-muted">Job ID:</span>
                                            <strong>{{ $job->job_id }}</strong>
                                        </li>
                                        <li class="mb-3">
                                            <span class="text-muted">Priority:</span>
                                            <span class="badge bg-{{ $job->priority === 'low' ? 'success' : ($job->priority === 'medium' ? 'warning' : 'danger') }}">{{ ucfirst($job->priority) }}</span>
                                        </li>
                                        <li class="mb-3">
                                            <span class="text-muted">Status:</span>
                                            <span class="badge bg-{{ $job->status === 'draft' ? 'secondary' : ($job->status === 'active' ? 'success' : ($job->status === 'pending' ? 'warning' : 'primary')) }}">{{ ucfirst($job->status) }}</span>
                                        </li>
                                        <li class="mb-3">
                                            <span class="text-muted">Created:</span>
                                            <strong>{{ $job->created_at->format('d M Y H:i') }}</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Job Documents</h5>
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                            <i class="ri-upload-cloud-2-line me-1"></i> Upload
                        </button>
                    </div>

                    <div id="documentsContainer">
                        <p class="text-muted text-center py-4">Loading documents...</p>
                    </div>

                </div>

                <!-- Checklists Tab -->
                <div class="tab-pane fade" id="checklists" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Job Checklists (<span id="checklistsCount">0</span>)</h5>
                        @hasanyrole('Super Admin|Admin')
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignChecklistModal">
                            Assign Checklist
                        </button>
                        @endhasanyrole
                    </div>

                    <div id="checklistsContainer">
                        <p class="text-muted text-center py-4">Loading checklists...</p>
                    </div>
                </div>
                

                <div class="tab-pane fade" id="assignments" role="tabpanel">
                    <h5 class="mb-3">Assignments</h5>
                    @if($job->assignments->isEmpty())
                        <p class="text-muted text-center py-4">No assignments yet</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Worker</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($job->assignments as $i => $assignment)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $assignment->worker->name ?? '-' }}</td>
                                        <td>{{ $assignment->formatted_date }}</td>
                                        <td>{{ $assignment->formatTime($assignment->start_time) ?? '-' }}</td>
                                        <td>{{ $assignment->formatTime($assignment->end_time) ?? '-' }}</td>
                                        <td>{{ $assignment->note ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="documentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="service_job_id" value="{{ $job->id }}">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select class="form-select" name="type" id="docType">
                            <option value="document">Document</option>
                            <option value="photo">Photo</option>
                            <option value="invoice">Invoice</option>
                            <option value="receipt">Receipt</option>
                            <option value="drawing">Drawing</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" placeholder="Optional title">
                    </div>

                    <div class="mb-3 d-none" id="amountBox">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" placeholder="Enter amount">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Assign Checklist Modal -->
<div class="modal fade" id="assignChecklistModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Checklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div id="checklistListContainer">
                    <p class="text-muted text-center py-4">Loading checklists...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')

<script>
    var isAdminOrSuper = {{ auth()->user()->hasAnyRole(['Super Admin', 'Admin']) ? 'true' : 'false' }};
</script>

<script>
$(function() {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var jobId = {{ $job->id }};

    loadNotes();
    loadDocuments();
    loadChecklists();
    loadChecklistsForSelection();

    $('#jobTabs a').on('click', function (e) {
        e.preventDefault();
        var tabId = $(this).attr('href');
        
        if (tabId === '#documents') {
            loadDocuments();
        } else if (tabId === '#checklists') {
            loadChecklists();
        }
    });

    function loadNotes() {
        $.get("{{ url('/admin/service-job') }}/" + jobId + "/notes", function(res) {
            var html = '';
            if (res.notes.length === 0) {
                html = '<p class="text-muted text-center py-4">No notes yet</p>';
            } else {
                res.notes.forEach(function(note) {
                    html += `
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${note.created_by}</strong>
                                    <p class="mb-2 text-muted" style="font-size: 12px;">${note.created_at}</p>
                                    <p class="mb-0">${note.note}</p>
                                </div>
                                <button class="btn btn-sm btn-outline-danger mt-1 deleteNoteBtn" data-id="${note.id}">
                                    <i class="ri-delete-bin-fill"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                });
                $('#notesCount').text(res.notes.length);
            }
            $('#notesContainer').html(html);
        });
    }

    $('#noteForm').on('submit', function(e) {
        e.preventDefault();
        
        var noteText = $('#noteText').val().trim();
        if (!noteText) {
            showError('Please enter a note');
            return;
        }

        $.post("{{ route('note.store') }}", {
            service_job_id: jobId,
            note: noteText
        }, function(res) {
            if (res.success) {
                $('#noteText').val('');
                loadNotes();
                showSuccess('Note added successfully');
            }
        }).fail(function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON.errors) {
                let first = Object.values(xhr.responseJSON.errors)[0][0];
                showError(first);
            } else {
                showError(xhr.responseJSON?.message ?? 'Error');
            }
        });
    });

    $('#docType').on('change', function() {
        let type = $(this).val();
        if (type === 'invoice' || type === 'receipt') {
            $('#amountBox').removeClass('d-none');
        } else {
            $('#amountBox').addClass('d-none');
            $('#amountBox input').val('');
        }
    });

    $('#documentForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('document.store') }}",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#uploadDocumentModal').modal('hide');
                    $('#documentForm')[0].reset();
                    $('#amountBox').addClass('d-none');

                    loadDocuments();
                    showSuccess(res.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    let first = Object.values(xhr.responseJSON.errors)[0][0];
                    showError(first);
                } else {
                    showError(xhr.responseJSON?.message ?? 'Error');
                }
            }
        });
    });

    $(document).on('click', '.deleteNoteBtn', function() {
        var noteId = $(this).data('id');
        if (confirm('Are you sure?')) {
            $.ajax({
                url: "{{ url('/admin/note') }}/" + noteId,
                method: 'DELETE',
                success: function(res) {
                    if (res.success) {
                        loadNotes();
                        showSuccess('Note deleted');
                    }
                },
                error: function() {
                    showError('Error deleting note');
                }
            });
        }
    });

    $(document).on('click', '.deleteDocBtn', function() {
        let id = $(this).data('id');

        if (confirm('Delete this document?')) {
            $.ajax({
                url: "{{ url('/admin/document') }}/" + id,
                method: "DELETE",
                success: function(res) {
                    if (res.success) {
                        loadDocuments();
                        showSuccess('Document deleted');
                    }
                },
                error: function() {
                    showError('Error deleting document');
                }
            });
        }
    });

    function loadDocuments() {
        $.get("{{ url('/admin/service-job') }}/" + jobId + "/documents", function(res) {

            $('#documentsCount').text(res.count);

            var html = '';
            if (res.documents.length === 0) {
                html = '<p class="text-muted text-center py-4">No documents uploaded yet</p>';
            } else {
                res.documents.forEach(function(doc) {

                    let amountHtml = '';
                    if (doc.amount) {
                        amountHtml = `<span class="badge bg-light text-dark">£ ${doc.amount}</span>`;
                    }

                    html += `
                        <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${doc.title ?? doc.type}</strong>
                                <div class="text-muted" style="font-size: 13px;">
                                    ${doc.type} • ${doc.created_by} • ${doc.created_at}
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                ${amountHtml}
                                <a class="btn btn-sm btn-outline-primary" target="_blank" href="${doc.file_url}">
                                   <i class="ri-download-2-line"></i> View
                                </a>
                                <button class="btn btn-sm btn-outline-danger deleteDocBtn" data-id="${doc.id}">
                                    <i class="ri-delete-bin-fill"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            $('#documentsContainer').html(html);
        });
    }

    function loadChecklists() {
        $.get("/admin/service-job/" + jobId + "/checklists", function(res) {
            let html = '';
            let count = res.checklists.length;

            $('#checklistsCount').text(count);
            $('#navChecklistsCount').text(count);

            if (count === 0) {
                html = '<p class="text-muted text-center py-4">No checklists assigned yet</p>';
            } else {
                res.checklists.forEach(function(item, index) {
                    let itemsHtml = '';
                    if (item.items && item.items.length > 0) {
                        item.items.forEach(function(checkItem) {
                            let typeLabel = checkItem.type
                                .replace(/_/g, ' ')
                                .split(' ')
                                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                                .join(' ');
                            
                            itemsHtml += `
                                <div class="ps-3 mb-2 pb-2 border-start border-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge bg-light text-dark mt-1">${typeLabel}</span>
                                        <span class="flex-grow-1">${checkItem.question}</span>
                                        ${checkItem.is_required ? '<span class="badge bg-danger ms-2 mt-1">Required</span>' : ''}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        itemsHtml = '<p class="text-muted text-center py-2 ps-3">No items</p>';
                    }

                    html += `
                        <div class="accordion mb-3" id="checklistAccordion${index}">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                        <strong>${item.title}</strong>
                                        <span class="badge bg-info ms-2">${item.items ? item.items.length : 0} items</span>
                                    </button>
                                </h2>
                                <div id="collapse${index}" class="accordion-collapse collapse" data-bs-parent="#checklistAccordion${index}">
                                    <div class="accordion-body p-3">
                                        ${itemsHtml}
                                        <div class="text-end mt-3">
                                            ${isAdminOrSuper ? '<button class="btn btn-sm btn-outline-danger deleteChecklistBtn" data-id="' + item.id + '"><i class="ri-delete-bin-fill"></i> Remove</button>' : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            $('#checklistsContainer').html(html);
        });
    }

    function loadChecklistItems(checklistId) {
        $.get("/admin/checklist/" + checklistId + "/items", function(res) {
            let itemsHtml = '';
            if (res.items && res.items.length > 0) {
                res.items.forEach(function(item) {
                    itemsHtml += `
                        <div class="ps-3 mb-2 pb-2 border-start border-2">
                            <div class="d-flex align-items-start">
                                <span class="badge bg-light text-dark me-2 mt-1">${item.type}</span>
                                <span class="flex-grow-1">${item.question}</span>
                                ${item.is_required ? '<span class="badge bg-danger ms-2">Required</span>' : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                itemsHtml = '<p class="text-muted text-center py-2 ps-3">No items</p>';
            }
            $('#items-' + checklistId).html(itemsHtml);
        });
    }

    function loadChecklistsForSelection() {
        $.get("/admin/checklist/active/list", function(res) {
            let html = '';
            if (res.checklists && res.checklists.length > 0) {
                html = '<div class="accordion" id="checklistSelectAccordion">';
                res.checklists.forEach(function(checklist, index) {
                    let itemsHtml = '';
                    if (checklist.items && checklist.items.length > 0) {
                        checklist.items.forEach(function(item) {
                            let typeLabel = item.type
                                .replace(/_/g, ' ')
                                .split(' ')
                                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                                .join(' ');
                            
                            itemsHtml += `
                                <div class="ps-3 mb-2 pb-2 border-start border-2">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge bg-light text-dark mt-1">${typeLabel}</span>
                                        <span class="flex-grow-1">${item.question}</span>
                                        ${item.is_required ? '<span class="badge bg-danger ms-2 mt-1">Required</span>' : ''}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        itemsHtml = '<p class="text-muted ps-3">No items</p>';
                    }

                    html += `
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#checklistItem${index}">
                                    <strong>${checklist.title}</strong>
                                    <span class="badge bg-info ms-2">${checklist.items ? checklist.items.length : 0} items</span>
                                </button>
                            </h2>
                            <div id="checklistItem${index}" class="accordion-collapse collapse" data-bs-parent="#checklistSelectAccordion">
                                <div class="accordion-body">
                                    ${itemsHtml}
                                    <p class="card-text text-muted mb-3" style="font-size: 13px;">${checklist.description ?? ''}</p>
                                    <button class="btn btn-sm btn-primary w-100 assignChecklistBtn" data-checklist-id="${checklist.id}">
                                        <i class="ri-check-line me-1"></i> Select This Checklist
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html = '<p class="text-muted text-center py-4">No checklists available</p>';
            }
            $('#checklistListContainer').html(html);
        });
    }

    $(document).on('click', '.assignChecklistBtn', function(e) {
        e.preventDefault();
        let checklistId = $(this).data('checklist-id');

        $.post("{{ route('checklist.service-job.store') }}", {
            service_job_id: jobId,
            checklist_id: checklistId
        }, function(res) {
            if (res.success) {
                $('#assignChecklistModal').modal('hide');
                loadChecklists();
                loadChecklistsForSelection();
                showSuccess(res.message);
            } else {
                showError(res.message);
            }
        }).fail(function(xhr) {
            showError(xhr.responseJSON?.message || 'Error assigning checklist');
        });
    });

    $(document).on('click', '.deleteChecklistBtn', function() {
        let id = $(this).data('id');
        if (!confirm('Remove this checklist from the job?')) return;

        $.ajax({
            url: "/admin/service-job/checklist/" + id,
            method: 'DELETE',
            success: function(res) {
                if (res.success) {
                    loadChecklists();
                    loadChecklistsForSelection();
                    showSuccess(res.message);
                }
            },
            error: function() {
                showError('Error removing checklist');
            }
        });
    });
});
</script>
@endsection