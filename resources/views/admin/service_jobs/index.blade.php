@extends('admin.pages.master')
@section('title', 'Jobs')
@section('content')

<div class="container-fluid" id="newBtnSection">
    <div class="row mb-3">
        <div class="col-auto">
            <button class="btn btn-primary" id="newBtn">Add New Job</button>
        </div>
    </div>
</div>

<div class="container-fluid" id="addThisFormContainer" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h4 id="cardTitle">Add New Job</h4>
                </div>
                <div class="card-body">
                    <form id="createThisForm">
                        @csrf
                        <input type="hidden" id="codeid" name="id">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Job Title <span class="text-danger">*</span></label>
                                <input type="text" id="job_title" name="job_title" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Client <span class="text-danger">*</span>
                                    <a href="#" class="ms-2 small text-primary" id="quickAddClientBtn">+ Add New</a>
                                </label>
                                <select id="client_id" name="client_id" class="form-control select2">
                                    <option value="">Select Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" id="address_line1" name="address_line1" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" id="address_line2" name="address_line2" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" id="city" name="city" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Postcode <span class="text-danger">*</span></label>
                                <input type="text" id="postcode" name="postcode" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Instructions</label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="status1" name="status" class="form-control">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Priority <span class="text-danger">*</span></label>
                                <select id="priority" name="priority" class="form-control">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Estimated Hours</label>
                                <input type="number" step="0.5" id="estimated_hours" name="estimated_hours" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
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

<div class="container-fluid" id="contentContainer">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Jobs</h4>
            <select id="statusFilter" class="form-select w-auto">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="card-body">
            <table id="serviceJobTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Sl</th>
                        <th>Job ID</th>
                        <th>Job Title</th>
                        <th>Client</th>
                        <th>City</th>
                        <th>Postcode</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Est Hours</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="quickAddClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Add Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickClientForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="qc_name" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="qc_email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="qc_phone" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="quickClientSaveBtn">Save Client</button>
            </div>
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

    $('#quickAddClientBtn').click(function (e) {
        e.preventDefault();
        $('#quickClientForm')[0].reset();
        $('#quickAddClientModal').modal('show');
    });

    $('#quickClientSaveBtn').click(function () {
        var fd = new FormData(document.getElementById('quickClientForm'));

        $.ajax({
            url: "{{ route('client.store') }}",
            method: "POST",
            data: fd,
            contentType: false,
            processData: false,
            success: function (res) {
                var newOption = new Option(res.client.name, res.client.id, true, true);
                $('#client_id').append(newOption).trigger('change');
                $('#quickAddClientModal').modal('hide');
                showSuccess(res.message);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    let first = Object.values(xhr.responseJSON.errors)[0][0];
                    showError(first);
                } else showError(xhr.responseJSON?.message ?? 'Error');
            }
        });
    });

    var table = $('#serviceJobTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('serviceJob.index') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'job_id', name: 'job_id' },
            { data: 'job_title', name: 'job_title' },
            { data: 'client', name: 'client', orderable: false, searchable: false },
            { data: 'city', name: 'city' },
            { data: 'postcode', name: 'postcode' },
            { data: 'status', name: 'status' },
            { data: 'priority', name: 'priority' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'estimated_hours', name: 'estimated_hours', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('#statusFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#newBtn').click(function () {
        $('#createThisForm')[0].reset();
        $('#client_id').val(null).trigger('change');
        $('#codeid').val('');
        $('#cardTitle').text('Add New Job');
        $('#addBtn').val('Create').text('Create');
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
            ? "{{ route('serviceJob.store') }}"
            : "{{ route('serviceJob.update') }}";

        var fd = new FormData(document.getElementById('createThisForm'));
        if ($(btn).val() !== 'Create') fd.append('id', $('#codeid').val());

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

    $(document).on('click', '.EditBtn', function () {
        var id = $(this).data('id');

        $.get("{{ url('/admin/service-job') }}/" + id + "/edit", {}, function (res) {
            $('#codeid').val(res.id);
            $('#job_title').val(res.job_title);
            $('#client_id').val(res.client_id).trigger('change');
            $('#address_line1').val(res.address_line1);
            $('#address_line2').val(res.address_line2);
            $('#city').val(res.city);
            $('#postcode').val(res.postcode);
            $('#description').val(res.description);
            $('#instructions').val(res.instructions);
            $('#status1').val(res.status);
            $('#priority').val(res.priority);
            $('#estimated_hours').val(res.estimated_hours);
            $('#start_date').val(res.start_date);
            $('#end_date').val(res.end_date);

            $('#cardTitle').text('Update Job');
            $('#addBtn').val('Update').text('Update');
            $('#addThisFormContainer').show();
            $('#newBtn').hide();
            pagetop();
        });
    });

});
</script>
@endsection