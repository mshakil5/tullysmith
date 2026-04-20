@extends('admin.pages.master')
@section('title', 'Expenses')
@section('content')

@if($job)
<!-- Job Context Header -->
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white mb-1">{{ $job->job_title }}</h5>
                            <p class="mb-0 text-white-50">
                                <i class="ri-briefcase-line me-1"></i>{{ $job->job_id }} • 
                                <i class="ri-user-line me-1"></i>{{ $job->client->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="text-end">
                            <h3 class="text-white mb-0">£{{ number_format($job->totalExpenses(), 2) }}</h3>
                            <small class="text-white-50">Total Expenses</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Add New Button -->
<div class="container-fluid">
    <div class="row mb-3" id="newBtnSection">
        <div class="col-auto">
            <button class="btn btn-primary" id="newBtn">
                <i class="ri-add-line me-1"></i>Add New Expense
            </button>
        </div>
        @if($job)
        <div class="col-auto">
            <a href="{{ route('serviceJob.show', $job->id) }}" class="btn btn-outline-secondary">
                <i class="ri-arrow-left-line me-1"></i>Back to Job
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Form Container -->
<div class="container-fluid" id="addThisFormContainer" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 id="cardTitle">Add New Expense</h4>
                </div>
                <div class="card-body">
                    <form id="createThisForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="codeid" name="id">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Job <span class="text-danger">*</span></label>
                                <select id="service_job_id" name="service_job_id" class="form-control select2" required>
                                    <option value="">Select Job</option>
                                    @foreach($jobs as $j)
                                        <option value="{{ $j->id }}" {{ $job && $job->id == $j->id ? 'selected' : '' }}>
                                            {{ $j->job_id }} - {{ $j->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="invoice">Invoice</option>
                                    <option value="receipt">Receipt</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">£</span>
                                    <input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" id="invoice_date" name="invoice_date" class="form-control" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Title/Description</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="Optional description">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Upload Invoice/Receipt <span class="text-danger">*</span></label>
                                <input type="file" id="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Accepted: PDF, JPG, PNG (Max 5MB)</small>
                            </div>

                            <div class="col-md-12" id="currentFilePreview" style="display: none;">
                                <label class="form-label">Current File</label>
                                <div class="border rounded p-2">
                                    <a href="#" id="currentFileLink" target="_blank" class="text-primary">
                                        <i class="ri-file-line me-1"></i>View Current File
                                    </a>
                                </div>
                            </div>

                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" id="addBtn" class="btn btn-primary" value="Create">Create</button>
                            <button type="button" id="FormCloseBtn" class="btn btn-light">Cancel</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Expenses Table -->
<div class="container-fluid" id="contentContainer">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ri-money-pound-circle-line me-2"></i>
                {{ $job ? 'Job Expenses' : 'All Expenses' }}
            </h4>
        </div>
        <div class="card-body">
            <table id="expensesTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Sl</th>
                        @if(!$job)
                        <th>Job</th>
                        <th>Client</th>
                        @endif
                        <th>Type</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Invoice Date</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>


@endsection

@section('script')
<script>
$(function () {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var jobId = {{ $job ? $job->id : 'null' }};

    var columns = [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    ];

    @if(!$job)
    columns.push(
        { data: 'job', name: 'job' },
        { data: 'client', name: 'client' }
    );
    @endif

    columns.push(
        { data: 'type', name: 'type' },
        { data: 'title', name: 'title' },
        { data: 'amount', name: 'amount' },
        { data: 'invoice_date', name: 'invoice_date' },
        { data: 'created_by', name: 'created_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    );

    var table = $('#expensesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('expenses.index') }}",
            data: function (d) {
                if (jobId) {
                    d.job_id = jobId;
                }
            }
        },
        columns: columns
    });

    $('#newBtn').click(function () {
        $('#createThisForm')[0].reset();
        $('#service_job_id').val(jobId || '').trigger('change');
        $('#codeid').val('');
        $('#cardTitle').text('Add New Expense');
        $('#addBtn').val('Create').text('Create');
        $('#file').prop('required', true);
        $('#currentFilePreview').hide();
        $('#addThisFormContainer').show(300);
        $('#newBtn').hide();
    });

    $('#FormCloseBtn').click(function () {
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        $('#createThisForm')[0].reset();
    });

    $('#addBtn').click(function () {
        var btn = this;
        var url = $(btn).val() === 'Create'
            ? "{{ route('expenses.store') }}"
            : "{{ route('expenses.update') }}";

        var fd = new FormData(document.getElementById('createThisForm'));
        if ($(btn).val() !== 'Create') {
            fd.append('id', $('#codeid').val());
        }

        $.ajax({
            url: url,
            method: "POST",
            data: fd,
            contentType: false,
            processData: false,
            success: function (res) {
                showSuccess(res.message);
                $('#addThisFormContainer').hide();
                $('#newBtn').show();
                table.ajax.reload(null, false);
                $('#createThisForm')[0].reset();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    let first = Object.values(xhr.responseJSON.errors)[0][0];
                    showError(first);
                } else showError(xhr.responseJSON?.message ?? 'Error');
            }
        });
    });

    $(document).on('click', '.EditExpenseBtn', function () {
        var id = $(this).data('id');

        $.get("{{ url('/admin/expense') }}/" + id + "/edit", {}, function (res) {
            $('#codeid').val(res.id);
            $('#service_job_id').val(res.service_job_id).trigger('change');
            $('#type').val(res.type);
            $('#title').val(res.title);
            $('#amount').val(res.amount);
            $('#invoice_date').val(res.invoice_date);
            
            $('#currentFileLink').attr('href', '{{ asset('') }}' + res.file.replace(/^\//, ''));
            $('#currentFilePreview').show();
            $('#file').prop('required', false);

            $('#cardTitle').text('Update Expense');
            $('#addBtn').val('Update').text('Update');
            $('#addThisFormContainer').show();
            $('#newBtn').hide();
            pagetop();
        });
    });

    $(document).on('click', '.deleteExpenseBtn', function () {
        var id = $(this).data('id');
        
        showConfirm('Are you sure you want to delete this expense?').then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('/admin/expense') }}/" + id,
                method: 'DELETE',
                success: function (res) {
                    showSuccess(res.message);
                    table.ajax.reload(null, false);
                },
                error: function () {
                    showError('Error deleting expense');
                }
            });
        });
    });

});
</script>
@endsection