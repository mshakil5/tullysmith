@extends('admin.pages.master')
@section('title', 'Projects')
@section('content')

    <div class="container-fluid" id="newBtnSection">
        <div class="row mb-3">
            <div class="col-auto">
                <button class="btn btn-primary" id="newBtn">Add New Project</button>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="addThisFormContainer" style="display:none;">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 id="cardTitle">Add New Project</h4>
                    </div>
                    <div class="card-body">
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="id">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Client <span class="text-danger">*</span></label>
                                    <select id="client_id" name="client_id" class="form-control select2">
                                        <option value="">Select Client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Project Name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control summernote" id="description" name="description"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Latitude</label>
                                    <input type="number" id="latitude" name="latitude" class="form-control" step="0.0000001">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Longitude</label>
                                    <input type="number" id="longitude" name="longitude" class="form-control" step="0.0000001">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Project Area</label>
                                    <input type="text" id="project_area" name="project_area" class="form-control">
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
                <h4 class="card-title mb-0">Projects</h4>
            </div>
            <div class="card-body">
                <table id="projectTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Name</th>
                            <th>Client</th>
                            <th>Address</th>
                            <th>Project Area</th>
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
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#projectTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('project.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'client_name',
                        name: 'client_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'address',
                        name: 'address'
                    },
                    {
                        data: 'project_area',
                        name: 'project_area'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#newBtn').click(function() {
                $('#createThisForm')[0].reset();
                $(".summernote").summernote('code', '');
                $('#codeid').val('');
                $('#client_id').val('').trigger('change');
                $('#cardTitle').text('Add New Project');
                $('#addBtn').val('Create').text('Create');
                $('#addThisFormContainer').show(300);
                $('#newBtn').hide();
            });

            $('#FormCloseBtn').click(function() {
                $('#addThisFormContainer').hide(200);
                $('#newBtn').show();
                $('#createThisForm')[0].reset();
            });

            $('#addBtn').click(function() {
                var btn = this;
                var url = $(btn).val() === 'Create' ? "{{ route('project.store') }}" :
                    "{{ route('project.update') }}";
                var fd = new FormData(document.getElementById('createThisForm'));
                if ($(btn).val() !== 'Create') fd.append('id', $('#codeid').val());

                $.ajax({
                    url: url,
                    method: "POST",
                    data: fd,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        showSuccess(res.message);
                        $('#addThisFormContainer').hide();
                        $('#newBtn').show();
                        table.ajax.reload(null, false);
                        $('#createThisForm')[0].reset();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON.errors) {
                            let first = Object.values(xhr.responseJSON.errors)[0][0];
                            showError(first);
                        } else showError(xhr.responseJSON?.message ?? 'Error');
                    }
                });
            });

            $(document).on('click', '.EditBtn', function() {
                var id = $(this).data('id');
                $.get("{{ url('/admin/project') }}/" + id + "/edit", {}, function(res) {
                    $('#codeid').val(res.id);
                    $('#name').val(res.name);
                    $('#address').val(res.address);
                    $('#latitude').val(res.latitude);
                    $('#longitude').val(res.longitude);
                    $('#project_area').val(res.project_area);
                    $(".summernote").summernote('code', res.description ?? '');
                    $('#client_id').val(res.client_id).trigger('change');
                    
                    $('#cardTitle').text('Update Project');
                    $('#addBtn').val('Update').text('Update');
                    $('#addThisFormContainer').show();
                    $('#newBtn').hide();
                });
            });
        });
    </script>
@endsection