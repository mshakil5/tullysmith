@extends('admin.pages.master')
@section('title', 'Checklists')
@section('content')

<div class="container-fluid" id="newBtnSection">
    <div class="row mb-3">
        <div class="col-auto">
            <button class="btn btn-primary" id="newBtn">Add New Checklist</button>
        </div>
    </div>
</div>

<div class="container-fluid" id="addThisFormContainer" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h4 id="cardTitle">Add New Checklist</h4>
                </div>
                <div class="card-body">
                    <form id="createThisForm">
                        @csrf
                        <input type="hidden" id="codeid" name="id">

                        <div class="row g-3">

                            <div class="col-md-8">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Active</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control summernote" id="description" name="description"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Checklist Items</label>
                                <div id="itemsContainer">
                                    <div class="item-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <input type="text" placeholder="Question" class="form-control item-question" name="items[0][question]">
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-control item-type" name="items[0][type]">
                                                    <option value="yes_no">Yes / No</option>
                                                    <option value="yes_no_na">Yes / No / N/A</option>
                                                    <option value="text_input">Text Input</option>
                                                    <option value="photo_upload">Photo Upload</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input item-required" type="checkbox" name="items[0][is_required]">
                                                    <label class="form-check-label" style="font-size: 12px;">Required</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger btn-sm removeItemBtn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="addItemBtn" class="btn btn-success btn-sm mt-2">+ Add Item</button>
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
            <h4 class="card-title mb-0">Checklists</h4>
        </div>
        <div class="card-body">
            <table id="checklistTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Sl</th>
                        <th>Title</th>
                        <th>Items</th>
                        <th>Active</th>
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

    var table = $('#checklistTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('checklist.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'title', name: 'title' },
            { data: 'items_count', name: 'items_count', orderable: false, searchable: false },
            { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    var itemCount = 1;

    $('#newBtn').click(function () {
        $('#createThisForm')[0].reset();
        $('#codeid').val('');
        $('#itemsContainer').html(`
            <div class="item-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" placeholder="Question" class="form-control item-question" name="items[0][question]">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control item-type" name="items[0][type]">
                            <option value="yes_no">Yes / No</option>
                            <option value="yes_no_na">Yes / No / N/A</option>
                            <option value="text_input">Text Input</option>
                            <option value="photo_upload">Photo Upload</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2">
                            <input class="form-check-input item-required" type="checkbox" name="items[0][is_required]">
                            <label class="form-check-label" style="font-size: 12px;">Required</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm removeItemBtn">Remove</button>
                    </div>
                </div>
            </div>
        `);
        itemCount = 1;
        $('#is_active').prop('checked', true);
        $(".summernote").summernote('code', '');
        $('#cardTitle').text('Add New Checklist');
        $('#addBtn').val('Create').text('Create');
        $('#addThisFormContainer').show(300);
        $('#newBtn').hide();
    });

    $('#FormCloseBtn').click(function () {
        $('#addThisFormContainer').hide(200);
        $('#newBtn').show();
        $('#createThisForm')[0].reset();
    });

    $('#addItemBtn').click(function () {
        var newItem = `
            <div class="item-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" placeholder="Question" class="form-control item-question" name="items[${itemCount}][question]">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control item-type" name="items[${itemCount}][type]">
                            <option value="yes_no">Yes / No</option>
                            <option value="yes_no_na">Yes / No / N/A</option>
                            <option value="text_input">Text Input</option>
                            <option value="photo_upload">Photo Upload</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2">
                            <input class="form-check-input item-required" type="checkbox" name="items[${itemCount}][is_required]">
                            <label class="form-check-label" style="font-size: 12px;">Required</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm removeItemBtn">Remove</button>
                    </div>
                </div>
            </div>
        `;
        $('#itemsContainer').append(newItem);
        itemCount++;
    });

    $(document).on('click', '.removeItemBtn', function () {
        $(this).closest('.item-row').remove();
    });

    $('#addBtn').click(function () {
        var btn = this;
        var url = $(btn).val() === 'Create' ? "{{ route('checklist.store') }}" : "{{ route('checklist.update') }}";
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

        $.get("{{ url('/admin/checklist') }}/" + id + "/edit", {}, function (res) {
            $('#codeid').val(res.id);
            $('#title').val(res.title);
            $('#is_active').prop('checked', res.is_active);
            $(".summernote").summernote('code', res.description ?? '');

            $('#itemsContainer').html('');
            itemCount = 0;
            
            if (res.items && res.items.length > 0) {
                res.items.forEach(function(item, index) {
                    var checked = item.is_required ? 'checked' : '';
                    var itemHtml = `
                        <div class="item-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="text" placeholder="Question" class="form-control item-question" value="${item.question}" name="items[${index}][question]">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-control item-type" name="items[${index}][type]">
                                        <option value="yes_no" ${item.type === 'yes_no' ? 'selected' : ''}>Yes / No</option>
                                        <option value="yes_no_na" ${item.type === 'yes_no_na' ? 'selected' : ''}>Yes / No / N/A</option>
                                        <option value="text_input" ${item.type === 'text_input' ? 'selected' : ''}>Text Input</option>
                                        <option value="photo_upload" ${item.type === 'photo_upload' ? 'selected' : ''}>Photo Upload</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input item-required" type="checkbox" name="items[${index}][is_required]" ${checked}>
                                        <label class="form-check-label" style="font-size: 12px;">Required</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm removeItemBtn">Remove</button>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#itemsContainer').append(itemHtml);
                    itemCount++;
                });
            }

            $('#cardTitle').text('Update Checklist');
            $('#addBtn').val('Update').text('Update');
            $('#addThisFormContainer').show();
            $('#newBtn').hide();
        });
    });

    $(document).on('change', '.toggle-status', function() {
        var id = $(this).data('id');
        var status = $(this).prop('checked') ? 1 : 0;
        $.post("{{ route('checklist.toggleStatus') }}", {
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