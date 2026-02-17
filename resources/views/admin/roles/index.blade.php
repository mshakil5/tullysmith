@extends('admin.pages.master')
@section('title', 'Roles')
@section('content')

<div class="container-fluid" id="newBtnSection">
    <div class="row mb-3">
        <div class="col text-end">
            <button class="btn btn-primary" id="newBtn">Add New Role</button>
        </div>
    </div>
</div>

<div class="container-fluid" id="addThisFormContainer" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 id="cardTitle">Add New Role</h4>
                </div>
                <div class="card-body">
                    <form id="createThisForm">
                        @csrf
                        <input type="hidden" id="roleId" name="id">

                        <div class="mb-3">
                            <label>Role Name <span class="text-danger">*</span></label>
                            <input type="text" id="roleName" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Assign Permissions</label>
                            <div id="modulesContainer"></div>
                        </div>

                        <div class="mb-3 text-end">
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
            <h4 class="card-title mb-0">Roles</h4>
        </div>
        <div class="card-body">
            <table id="roleTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Name</th>
                        <th>Permissions</th>
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

    var table = $('#roleTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('role.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'permissions_count', name: 'permissions_count', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    function loadModules() {
        $.get("{{ route('role.permissions') }}", function(res) {
            if (res.allPermissions && Object.keys(res.allPermissions).length > 0) {
                renderPermissions(res.allPermissions);
            } else {
                $('#modulesContainer').html('<p class="text-warning">No permissions found</p>');
            }
        }).fail(function() {
            $('#modulesContainer').html('<p class="text-danger">Failed to load permissions</p>');
        });
    }

    function renderPermissions(groupedPermissions) {
        let html = '';

        $.each(groupedPermissions, function(resource, perms) {
            let resourceLabel = resource.charAt(0).toUpperCase() + resource.slice(1).replace(/-/g, ' ');

            html += '<div class="card mb-3">';
            html += '<div class="card-header">';
            html += '<h6><strong>' + resourceLabel + '</strong></h6>';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<div class="form-check form-switch mb-2">';
            html += '<input class="form-check-input module-checkbox" type="checkbox" id="module_' + resource + '" data-module="' + resource + '">';
            html += '<label class="form-check-label" for="module_' + resource + '"><strong>Select All</strong></label>';
            html += '</div>';
            html += '<div class="module-permissions">';

            $.each(perms, function(i, perm) {
                let permLabel = perm.label || perm.name;
                html += '<div class="form-check ms-3">';
                html += '<input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="' + perm.name + '" id="perm_' + perm.name + '" data-module="' + resource + '">';
                html += '<label class="form-check-label" for="perm_' + perm.name + '">' + permLabel + '</label>';
                html += '</div>';
            });

            html += '</div></div></div>';
        });

        $('#modulesContainer').html(html);
    }

    $(document).on('change', '.module-checkbox', function() {
        let isChecked = $(this).is(':checked');
        $(this).closest('.card').find('.permission-checkbox').prop('checked', isChecked);
    });

    $('#newBtn').click(function() {
        $('#createThisForm')[0].reset();
        $('#roleId').val('');
        $('#cardTitle').text('Add New Role');
        $('#addBtn').val('Create').text('Create');
        loadModules();
        $('#addThisFormContainer').show(300);
        $('#newBtn').hide();
    });

    $('#FormCloseBtn').click(function() {
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        $('#createThisForm')[0].reset();
    });

    $('#addBtn').click(function() {
        let permissions = [];
        $('#createThisForm').find('.permission-checkbox:checked').each(function() {
            permissions.push($(this).val());
        });

        let btn = this;
        let url = $(btn).val() === 'Create'
            ? "{{ route('role.store') }}"
            : "{{ route('role.update') }}";

        let data = {
            name: $('#roleName').val(),
            permissions: permissions
        };

        if ($(btn).val() !== 'Create') {
            data.id = $('#roleId').val();
        }

        $.ajax({
            url: url,
            method: "POST",
            data: data,
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
                } else {
                    showError(xhr.responseJSON?.message ?? 'Something went wrong');
                }
            }
        });
    });

    $(document).on('click', '.EditBtn', function() {
        let id = $(this).data('id');

        $.get("{{ url('/admin/roles') }}/" + id + "/edit", function(res) {
            $('#roleId').val(res.role.id);
            $('#roleName').val(res.role.name);
            $('#cardTitle').text('Edit Role');
            $('#addBtn').val('Update').text('Update');

            renderPermissions(res.allPermissions);

            setTimeout(function() {
                $.each(res.rolePermissions, function(resource, perms) {
                    $.each(perms, function(i, perm) {
                        $('input[name="permissions[]"][value="' + perm.name + '"]').prop('checked', true);
                    });
                });
            }, 200);

            $('#addThisFormContainer').show();
            $('#newBtn').hide();
        });
    });
});
</script>
@endsection
