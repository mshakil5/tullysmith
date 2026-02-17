@extends('admin.pages.master')
@section('title', 'Clients')
@section('content')

    <div class="container-fluid" id="newBtnSection">
        <div class="row mb-3">
            <div class="col-auto">
                <button class="btn btn-primary" id="newBtn">Add New Client</button>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="addThisFormContainer" style="display:none;">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4 id="cardTitle">Add New Client</h4>
                    </div>
                    <div class="card-body">
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="id">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Primary Contact</label>
                                    <input type="text" id="primary_contact" name="primary_contact" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" id="phone" name="phone" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Additional Info</label>
                                    <textarea class="form-control summernote" id="additional_info" name="additional_info"></textarea>
                                </div>
                                <div class="col-6 d-none">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" id="password" name="password" class="form-control">
                                </div>
                                <div class="col-6 d-none">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
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
                <h4 class="card-title mb-0">Clients</h4>
            </div>
            <div class="card-body">
                <table id="clientTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Contact</th>
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
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#clientTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('client.index') }}",
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
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone',
                        name: 'phone'
                    },
                    {
                        data: 'primary_contact',
                        name: 'primary_contact'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
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
                $('#cardTitle').text('Add New Client');
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
                var url = $(btn).val() === 'Create' ? "{{ route('client.store') }}" :
                    "{{ route('client.update') }}";
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
                $.get("{{ url('/admin/client') }}/" + id + "/edit", {}, function(res) {
                    $('#codeid').val(res.id);
                    $('#name').val(res.name);
                    $('#email').val(res.email);
                    $('#phone').val(res.phone);
                    $('#primary_contact').val(res.primary_contact);
                    $('#address').val(res.address);
                    $(".summernote").summernote('code', res.additional_info ?? '');
                    $('#password, #password_confirmation').prop('required', false);
                    $('#cardTitle').text('Update Client');
                    $('#addBtn').val('Update').text('Update');
                    $('#addThisFormContainer').show();
                    $('#newBtn').hide();
                });
            });

            $(document).on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                var status = $(this).prop('checked') ? 1 : 0;
                $.post("{{ route('client.toggleStatus') }}", {
                    id: id,
                    status: status
                }, function(res) {
                    showSuccess(res.message);
                    table.ajax.reload(null, false);
                }).fail(function() {
                    showError('Failed');
                });
            });
        });
    </script>
@endsection
