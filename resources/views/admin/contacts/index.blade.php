@extends('admin.pages.master')
@section('title', 'Contacts')
@section('content')

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Contact Messages</h4>
            </div>
            <div class="card-body">
                <table id="contactTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sl</th>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <span id="v_name"></span></p>
                    <p><strong>Email:</strong> <span id="v_email"></span></p>
                    <p><strong>Phone:</strong> <span id="v_phone"></span></p>
                    <p><strong>Subject:</strong> <span id="v_subject"></span></p>
                    <p><strong>Date:</strong> <span id="v_date"></span></p>
                    <hr>
                    <p><strong>Message:</strong></p>
                    <div class="border p-3" id="v_message"></div>
                </div>
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

            var table = $('#contactTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('contacts.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'full_name',
                        name: 'full_name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'subject',
                        name: 'subject'
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
                    }
                ]
            });

            // View
            $(document).on('click', '.viewBtn', function() {
                var id = $(this).data('id');
                $.get("{{ url('/admin/contacts') }}/" + id, {}, function(res) {
                    $('#v_name').text(res.first_name + ' ' + res.last_name);
                    $('#v_email').text(res.email);
                    $('#v_phone').text(res.phone ?? '');
                    $('#v_subject').text(res.subject);
                    $('#v_date').text(res.formatted_date);
                    $('#v_message').text(res.message);
                    $('#viewModal').modal('show');
                });
            });

            // Toggle Status
            $(document).on('change', '.toggle-status', function() {
                let id = $(this).data('id');
                let status = $(this).prop('checked') ? 1 : 0;
                $.post("{{ route('contacts.toggleStatus') }}", {
                    id: id,
                    status: status
                }, function(res) {
                    showSuccess(res.message);
                    table.ajax.reload(null, false);
                }).fail(() => showError('Failed'));
            });
        });
    </script>
@endsection
