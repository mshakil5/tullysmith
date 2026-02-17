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
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select id="project_id" name="project_id" class="form-control select2">
                                    <option value="">Select Project</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" data-client-name="{{ $project->client->name }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Client</label>
                                <input type="text" id="client_name" class="form-control" readonly>
                                <input type="hidden" id="client_id" name="client_id">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control summernote" id="description" name="description"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Instructions</label>
                                <textarea class="form-control summernote" id="instructions" name="instructions"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
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
                                    <option value="">Select Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Estimated Hours</label>
                                <input type="text" id="estimated_hours" name="estimated_hours" class="form-control" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date Time</label>
                                <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date Time</label>
                                <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control">
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
        <div class="card-header">
            <h4 class="card-title mb-0">Jobs</h4>
        </div>
        <div class="card-body">
            <table id="serviceJobTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Sl</th>
                        <th>Job ID</th>
                        <th>Job Title</th>
                        <th>Client</th>
                        <th>Project</th>
                        <th>Address</th>
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

@endsection

@section('script')
<script>
$(function () {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var table = $('#serviceJobTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('serviceJob.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'job_id', name: 'job_id' },
            { data: 'job_title', name: 'job_title' },
            { data: 'client', name: 'client', orderable: false, searchable: false },
            { data: 'project', name: 'project', orderable: false, searchable: false },
            { data: 'address', name: 'address' },
            { data: 'status', name: 'status' },
            { data: 'priority', name: 'priority' },
            { data: 'start_datetime', name: 'start_datetime' },
            { data: 'end_datetime', name: 'end_datetime' },
            { data: 'estimated_hours', name: 'estimated_hours', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $('#newBtn').click(function () {
        $('#createThisForm')[0].reset();
        $('#project_id').val(null).trigger('change');
        $('#client_name').val('');
        $('#client_id').val('');
        $(".summernote").summernote('code', '');
        $('#codeid').val('');
        $('#estimated_hours').val('0');
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

    $(document).on('change', '#project_id', function() {
        var selectedOption = $(this).find(':selected');
        var clientId = selectedOption.data('client-id');
        var clientName = selectedOption.data('client-name');
        
        $('#client_id').val(clientId);
        $('#client_name').val(clientName);
    });

    $(document).on('change', '#start_datetime, #end_datetime', function() {
        calculateEstimatedHours();
    });

    function calculateEstimatedHours() {
        var startDateTime = $('#start_datetime').val();
        var endDateTime = $('#end_datetime').val();

        if (startDateTime && endDateTime) {
            var start = new Date(startDateTime);
            var end = new Date(endDateTime);
            var diffMs = end - start;
            var diffHours = (diffMs / (1000 * 60 * 60)).toFixed(2);
            $('#estimated_hours').val(diffHours);
        }
    }

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
            $('#project_id').val(res.project_id).trigger('change');
            $('#client_id').val(res.client_id);
            $('#client_name').val(res.client.name);
            $('#address').val(res.address);

            $(".summernote#description").summernote('code', res.description ?? '');
            $(".summernote#instructions").summernote('code', res.instructions ?? '');

            $('#status1').val(res.status);
            $('#priority').val(res.priority);

            $('#start_datetime').val(res.start_datetime);
            $('#end_datetime').val(res.end_datetime);
            $('#estimated_hours').val(res.estimated_hours ?? '0');

            $('#cardTitle').text('Update Job');
            $('#addBtn').val('Update').text('Update');
            $('#addThisFormContainer').show();
            $('#newBtn').hide();
        });
    });

});
</script>
@endsection