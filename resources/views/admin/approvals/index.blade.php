@extends('admin.pages.master')
@section('title', 'Approvals')
@section('content')

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h2>Approvals</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="approvalTabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-status="pending">
                        Pending
                        <span class="badge bg-danger ms-1" id="pendingBadge">{{ $pendingCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="approved">Approved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="rejected">Rejected</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="all">All</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div id="approvalsContainer">
                <p class="text-muted text-center py-4">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <p class="text-center text-muted">Loading...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(function () {
        var currentStatus = 'pending';

        loadApprovals(currentStatus);

        $('#approvalTabs a').on('click', function (e) {
            e.preventDefault();
            $('#approvalTabs a').removeClass('active');
            $(this).addClass('active');
            currentStatus = $(this).data('status');
            loadApprovals(currentStatus);
        });

        function typeIcon(type) {
            if (type === 'checklist') return '<div style="width:40px;height:40px;background:#fff8e1;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="ri-checkbox-multiple-line" style="color:#f59e0b;font-size:20px;"></i></div>';
            if (type === 'document') return '<div style="width:40px;height:40px;background:#e8f5e9;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="ri-file-text-line" style="color:#22c55e;font-size:20px;"></i></div>';
            if (type === 'timelog') return '<div style="width:40px;height:40px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="ri-time-line" style="color:#16a34a;font-size:20px;"></i></div>';
            return '<div style="width:40px;height:40px;background:#e3f2fd;border-radius:10px;display:flex;align-items:center;justify-content:center;"><i class="ri-sticky-note-line" style="color:#3b82f6;font-size:20px;"></i></div>';
        }

        function loadApprovals(status) {
            $('#approvalsContainer').html('<p class="text-muted text-center py-4">Loading...</p>');

            $.get("{{ route('approvals.index') }}", { status: status }, function (res) {
                $('#pendingBadge').text(res.pending_count);

                var html = '';
                if (res.items.length === 0) {
                    html = '<p class="text-muted text-center py-4">No items found</p>';
                } else {
                    res.items.forEach(function (item) {
                        html += `
                            <div class="border rounded p-3 mb-2 d-flex align-items-center gap-3 approval-item" 
                                style="cursor:pointer;" data-type="${item.type}" data-id="${item.id}">
                                ${typeIcon(item.type)}
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-dark border">${item.type}</span>
                                        <small class="text-muted">${item.created_at}</small>
                                    </div>
                                    <strong>${item.title}</strong>
                                    <div class="text-muted" style="font-size:13px;">${item.job} &bull; ${item.created_by}</div>
                                </div>
                                <span class="badge bg-${item.status === 'pending' ? 'warning' : (item.status === 'approved' ? 'success' : 'danger')} text-capitalize">${item.status}</span>
                            </div>
                        `;
                    });
                }

                $('#approvalsContainer').html(html);
            });
        }

        var currentItemType = null;
        var currentItemId   = null;

        $(document).on('click', '.approval-item', function () {
            currentItemType = $(this).data('type');
            currentItemId   = $(this).data('id');

            var typeLabel = currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1);
            $('#detailModalTitle').text('Review ' + typeLabel);
            $('#detailModalBody').html('<p class="text-center text-muted py-4">Loading...</p>');
            $('#detailModal').modal('show');

            $.get("{{ url('/admin/approvals') }}/" + currentItemType + "/" + currentItemId, function (html) {
                $('#detailModalBody').html(html);
            });
        });

        $(document).on('click', '.actionBtn', function () {
            var action         = $(this).data('action');
            var rejectionReason = $('#rejectionReason').val();

            $.post("{{ url('/admin/approvals') }}/" + currentItemType + "/" + currentItemId + "/action", {
                action: action,
                rejection_reason: rejectionReason,
                _token: $('meta[name="csrf-token"]').attr('content')
            }, function (res) {
                if (res.success) {
                    $('#detailModal').modal('hide');
                    loadApprovals(currentStatus);
                    showSuccess('Action successful');
                }
            });
        });
    });
</script>
@endsection