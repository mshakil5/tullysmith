@extends('admin.pages.master')
@section('title', 'Announcements')
@section('content')

    <div class="container-fluid" id="newBtnSection">
        <div class="row mb-3">
            <div class="col-auto">
                <button class="btn btn-primary" id="newBtn">Add New Announcement</button>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="addThisFormContainer" style="display:none;">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 id="cardTitle">Add New Announcement</h4>
                    </div>
                    <div class="card-body">
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="id">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control">
                                </div>
                                <div class="col-md-4 d-none">
                                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select id="priority" name="priority" class="form-control">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Content <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="4"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Job <span class="text-muted">(optional)</span></label>
                                    <select id="service_job_id" name="service_job_id" class="form-control select2">
                                        <option value="">All Jobs</option>
                                        @foreach ($jobs as $job)
                                            <option value="{{ $job->id }}">{{ $job->job_id }} — {{ $job->job_title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expires On <span class="text-muted">(optional)</span></label>
                                    <input type="date" id="expires_at" name="expires_at" class="form-control">
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
                <h4 class="card-title mb-0">Announcements</h4>
            </div>
            <div class="card-body">
                <table id="announcementTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Title</th>
                            {{-- <th>Priority</th> --}}
                            <th>Job</th>
                            <th>Expires On</th>
                            <th>Status</th>
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
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        var table = $('#announcementTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('announcement.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'title',      name: 'title' },
                //{ data: 'priority',   name: 'priority', orderable: false, searchable: false },
                { data: 'job',        name: 'job',      orderable: false, searchable: false },
                { data: 'expires_at', name: 'expires_at' },
                { data: 'status',     name: 'status',   orderable: false, searchable: false },
                { data: 'action',     name: 'action',   orderable: false, searchable: false },
            ]
        });

        $('#newBtn').click(function () {
            clearForm();
            $('#addThisFormContainer').show(300);
            $('#newBtn').hide();
        });

        $('#FormCloseBtn').click(function () {
            $('#addThisFormContainer').hide(200);
            $('#newBtn').show();
            clearForm();
        });

        $('#addBtn').click(function () {
            var isCreate = $(this).val() === 'Create';
            var url = isCreate ? "{{ route('announcement.store') }}" : "{{ route('announcement.update') }}";
            var fd = new FormData(document.getElementById('createThisForm'));
            if (!isCreate) fd.append('id', $('#codeid').val());

            $.ajax({
                url: url,
                method: 'POST',
                data: fd,
                contentType: false,
                processData: false,
                success: function (res) {
                    showSuccess(res.message);
                    $('#addThisFormContainer').hide();
                    $('#newBtn').show();
                    table.ajax.reload(null, false);
                    clearForm();
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
            $.get("{{ url('/admin/announcement') }}/" + id + "/edit", {}, function (res) {
                $('#codeid').val(res.id);
                $('#title').val(res.title);
                $('#content').val(res.content);
                $('#priority').val(res.priority);
                $('#service_job_id').val(res.service_job_id).trigger('change');
                $('#expires_at').val(res.expires_at ? res.expires_at.substring(0, 10) : '');
                $('#cardTitle').text('Update Announcement');
                $('#addBtn').val('Update').text('Update');
                $('#addThisFormContainer').show();
                $('#newBtn').hide();
                $('html, body').animate({ scrollTop: 0 }, 300);
            });
        });

        $(document).on('change', '.toggle-status', function () {
            var id = $(this).data('id');
            var status = $(this).prop('checked') ? 1 : 0;
            $.post("{{ route('announcement.toggleStatus') }}", { id: id, status: status }, function (res) {
                showSuccess(res.message);
                table.ajax.reload(null, false);
            }).fail(function () { showError('Failed'); });
        });

        function clearForm() {
            $('#createThisForm')[0].reset();
            $('#codeid').val('');
            $('#service_job_id').val(null).trigger('change');
            $('#cardTitle').text('Add New Announcement');
            $('#addBtn').val('Create').text('Create');
        }
    });
</script>
@endsection