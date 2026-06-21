@extends('admin.pages.master')
@section('title', 'Expense Report')

@php $company = App\Models\CompanyDetails::firstOrCreate(); @endphp

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
            <div>
                <h4 class="mb-1 fw-bold"><i class="ri-money-pound-circle-line me-2 text-success"></i>Expense Report</h4>
            </div>
            <div class="d-flex gap-2">
                <a id="exportBtn" href="#" class="btn btn-soft-success btn-sm d-none"><i class="ri-download-2-line me-1"></i>Export CSV</a>
                <button class="btn btn-soft-secondary btn-sm" id="printBtn">
                    <i class="ri-file-pdf-line me-1"></i>PDF
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-medium small">Job Name</label>
                        <select id="filterJob" class="form-control select2">
                            <option value="">All Jobs</option>
                            @foreach ($jobs as $j)
                                <option value="{{ $j->id }}">{{ $j->job_title }} ({{ $j->job_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">From Date</label>
                        <input type="date" id="fromDate" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">To Date</label>
                        <input type="date" id="toDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Mode</label>
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-secondary mode-btn" data-mode="daily">Daily</button>
                            <button class="btn btn-primary mode-btn active" data-mode="weekly">Weekly</button>
                            <button class="btn btn-outline-secondary mode-btn" data-mode="monthly">Monthly</button>
                            <button class="btn btn-outline-secondary mode-btn" data-mode="all">All</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium small">Period</label>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="prevBtn"><i class="ri-arrow-left-s-line"></i></button>
                            <span id="periodLabel" class="fw-semibold text-center flex-grow-1 small text-truncate"></span>
                            <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="nextBtn"><i class="ri-arrow-right-s-line"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Print Header --}}
        <div class="print-only mb-4">
            <table width="100%" style="border-bottom:2px solid #1a2d52; padding-bottom:8px; margin-bottom:16px;">
                <tr>
                    <td width="70%" style="vertical-align: bottom;">
                        @if ($company->company_logo)
                            <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="50" style="margin-bottom:6px;"><br>
                        @endif
                        <div style="font-size:22px;font-weight:700;color:#1a2d52;letter-spacing:1px; line-height: 1.2;">EXPENSE REPORT</div>
                        <div style="font-size:12px;color:#666;margin-top:2px;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                    </td>
                    <td width="30%" style="text-align:right; vertical-align: bottom;">
                        <div style="display: inline-block; text-align: left; padding-bottom: 4px;">
                            <div class="mb-1">
                                <span class="small fw-semibold" style="min-width: 55px; display: inline-block;">Date:</span>
                                <span class="small fw-semibold" id="p_period_label">—</span>
                            </div>
                            <div class="mb-0">
                                <span class="small fw-semibold" style="min-width: 55px; display: inline-block;">Job:</span>
                                <span class="small fw-semibold" id="p_job_label">All Jobs</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4 no-print">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-money-pound-circle-line text-success fs-5"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-success" id="statTotal">—</h4>
                            <p class="text-muted mb-0 small">Total Amount</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-file-list-3-line text-primary fs-5"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-primary" id="statCount">—</h4>
                            <p class="text-muted mb-0 small">Total Expenses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-user-line text-info fs-5"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-info" id="statWorkers">—</h4>
                            <p class="text-muted mb-0 small">Workers</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ri-briefcase-line text-warning fs-5"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-warning" id="statJobs">—</h4>
                            <p class="text-muted mb-0 small">Jobs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Entries Table --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-success"></i>Expense List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 report-table">
                        <thead id="expensesHead">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Job</th>
                                <th>Title</th>
                                <th class="text-center">File</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="expensesTable">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Loading...</td>
                            </tr>
                        </tbody>
                        <tfoot id="expensesFoot">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total Amount</td>
                                <td></td>
                                <td class="text-end fw-bold" id="footTotal">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Amount in Words Banner --}}
        <div class="alert alert-success border-success mb-4 d-flex align-items-center gap-3" id="totalWordsBanner" style="display:none!important;">
            <i class="ri-information-line fs-4 text-success flex-shrink-0"></i>
            <div>
                <div class="small text-muted mb-0">Total Amount in Words</div>
                <div class="fw-bold fs-6" id="totalWordsText">—</div>
            </div>
        </div>

        {{-- Attachments Grid --}}
        <div id="attachmentsSection" style="display:none;">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-attachment-2 me-2 text-success"></i>Expense Attachments</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="attachmentsGrid"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- File Preview Modal --}}
    <div class="modal fade" id="filePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-2" id="filePreviewBody"></div>
            </div>
        </div>
    </div>

    <style>
        .print-only { display: none; }

        .report-table thead th {
            background: #1a2d52;
            color: #fff;
            border-color: #1a2d52;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .report-table tfoot td {
            background: #1a2d52;
            color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .date-separator td { border-top: 2px solid #dee2e6 !important; }

        #totalWordsBanner { display: flex !important; }

        .attachment-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .attachment-card a {
            display: block;
            overflow: hidden;
        }

        .attachment-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            transition: transform 0.2s;
        }

        .attachment-card a:hover img {
            transform: scale(1.03);
        }

        .attachment-card .att-pdf {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff5f5;
        }

        .attachment-card .att-amount {
            padding: 8px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            font-weight: 700;
            color: #059669;
        }

        @media print {
            @page { size: A4 portrait; margin: 1.2cm; }

            .no-print, .btn, button, select, input, nav, .sidebar, header { display: none !important; }

            body { background: #fff !important; font-size: 11px !important; }

            .container-fluid { padding: 0 !important; }

            .print-only { display: block !important; }

            .card {
                border: 1px solid #e5e7eb !important;
                box-shadow: none !important;
                break-inside: avoid;
                margin-bottom: 12px !important;
            }

            .report-table thead th {
                background: #1a2d52 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-table tfoot td {
                background: #1a2d52 !important;
                color: #fff !important;
            }

            .badge, * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #totalWordsBanner { display: flex !important; }

            #attachmentsSection { display: block !important; }

            .attachment-card {
                break-inside: avoid;
                border: 1px solid #ccc !important;
            }

            .attachment-card img { height: 120px !important; }

            .attachment-card .att-pdf { height: 120px !important; }
        }
    </style>
@endsection

@section('script')
    <script>
        $(function() {

            $('#printBtn').on('click', function() {
                window.open('{{ route('reports.expense.pdf') }}?' + $.param(params()), '_blank');
            });

            var mode = 'weekly', offset = 0;

            function params() {
                var from = $('#fromDate').val(), to = $('#toDate').val();
                return {
                    mode: (from && to) ? 'custom' : mode,
                    offset: (from && to) ? 0 : offset,
                    job_id: $('#filterJob').val(),
                    from_date: from,
                    to_date: to
                };
            }

            function load() {
                var isJobFiltered = $('#filterJob').val() !== '';

                $('#statTotal,#statCount,#statWorkers,#statJobs').html('<span class="spinner-border spinner-border-sm"></span>');
                $('#expensesTable').html('<tr><td colspan="6" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>');
                $('#attachmentsSection').hide();
                $('#attachmentsGrid').html('');
                $('#exportBtn').attr('href', '{{ route('reports.expense.export') }}?' + $.param(params()));

                $.get('{{ route('reports.expense.data') }}', params(), function(r) {
                    $('#periodLabel').text(r.label);
                    $('#p_period_label').text(r.label);
                    $('#p_job_label').text($('#filterJob option:selected').text() || 'All Jobs');
                    $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

                    $('#statTotal').text('£' + r.total_amount);
                    $('#statCount').text(r.total_count);
                    $('#statWorkers').text(r.unique_workers);
                    $('#statJobs').text(r.unique_jobs);
                    $('#footTotal').text('£' + r.total_amount);
                    $('#totalWordsText').text(r.total_words);
                    $('#totalWordsBanner').show();

                    // Dynamic thead
                    var theadHtml = '<tr><th>#</th><th>Date</th>';
                    if (!isJobFiltered) theadHtml += '<th>Job</th>';
                    theadHtml += '<th>Title</th><th class="text-center">File</th><th class="text-end">Amount</th></tr>';
                    $('#expensesHead').html(theadHtml);

                    // Dynamic tfoot
                    var tfootColspan = isJobFiltered ? 3 : 4;
                    $('#expensesFoot').html(
                        '<tr><td colspan="' + tfootColspan + '" class="text-end fw-bold">Total Amount</td>' +
                        '<td></td>' +
                        '<td class="text-end fw-bold">£' + r.total_amount + '</td></tr>'
                    );

                    if (!r.expenses || !r.expenses.length) {
                        var colspan = isJobFiltered ? 5 : 6;
                        $('#expensesTable').html('<tr><td colspan="' + colspan + '" class="text-center text-muted py-4">No data for this period</td></tr>');
                        return;
                    }

                    var imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    var tableHtml = '';
                    var attachHtml = '';
                    var hasAttachments = false;
                    var prevDate = null;

                    r.expenses.forEach(function(e, i) {
                        var isNewDate = e.date !== prevDate;
                        var rowClass = isNewDate && i > 0 ? 'date-separator' : '';

                        var fileHtml = '—';
                        if (e.file) {
                            if (imgExts.indexOf(e.file_ext) !== -1) {
                                fileHtml = '<img src="' + e.file + '" ' +
                                    'style="height:70px;width:75px;object-fit:cover;border-radius:4px;cursor:pointer;" ' +
                                    'data-bs-toggle="modal" data-bs-target="#filePreviewModal" ' +
                                    'data-src="' + e.file + '" data-type="image">';

                                attachHtml += '<div class="col-6 col-md-3">' +
                                    '<div class="attachment-card" style="cursor:pointer;" ' +
                                    'data-bs-toggle="modal" data-bs-target="#filePreviewModal" ' +
                                    'data-src="' + e.file + '" data-type="image">' +
                                    '<img src="' + e.file + '">' +
                                    '<div class="att-amount">£' + e.amount + '</div>' +
                                    '</div>' +
                                    '</div>';
                                hasAttachments = true;

                            } else {
                                fileHtml = '<button class="btn btn-xs btn-soft-info" ' +
                                    'data-bs-toggle="modal" data-bs-target="#filePreviewModal" ' +
                                    'data-src="' + e.file + '" data-type="pdf">' +
                                    '<i class="ri-file-pdf-line"></i></button>';

                                attachHtml += '<div class="col-6 col-md-3">' +
                                    '<div class="attachment-card" style="cursor:pointer;" ' +
                                    'data-bs-toggle="modal" data-bs-target="#filePreviewModal" ' +
                                    'data-src="' + e.file + '" data-type="pdf">' +
                                    '<div class="att-pdf">' +
                                    '<i class="ri-file-pdf-line text-danger" style="font-size:64px;"></i>' +
                                    '</div>' +
                                    '<div class="att-amount">£' + e.amount + '</div>' +
                                    '</div>' +
                                    '</div>';
                                hasAttachments = true;
                            }
                        }

                        tableHtml += '<tr class="' + rowClass + '">' +
                            '<td class="text-muted small">' + (i + 1) + '</td>' +
                            '<td class="small fw-semibold">' + e.date + '</td>';

                        if (!isJobFiltered) {
                            tableHtml += '<td><span class="fw-semibold small">' + e.job + '</span></td>';
                        }

                        tableHtml += '<td>' + e.title + '</td>' +
                            '<td class="text-center">' + fileHtml + '</td>' +
                            '<td class="text-end fw-bold text-success">£' + e.amount + '</td>' +
                            '</tr>';

                        prevDate = e.date;
                    });

                    $('#expensesTable').html(tableHtml);

                    if (hasAttachments) {
                        $('#attachmentsGrid').html(attachHtml);
                        $('#attachmentsSection').show();
                    }

                }).fail(function() {
                    alert('Failed to load report data.');
                });
            }

            $(document).on('click', '[data-bs-target="#filePreviewModal"]', function() {
                var src = $(this).data('src');
                var type = $(this).data('type');
                if (type === 'image') {
                    $('#filePreviewBody').html('<img src="' + src + '" class="img-fluid rounded">');
                } else {
                    $('#filePreviewBody').html('<iframe src="' + src + '" style="width:100%;height:500px;border:none;"></iframe>');
                }
            });

            $('#filePreviewModal').on('hidden.bs.modal', function() {
                $('#filePreviewBody').html('');
            });

            $('#fromDate,#toDate').on('change', function() {
                var from = $('#fromDate').val(), to = $('#toDate').val();
                $('.mode-btn,#prevBtn,#nextBtn').prop('disabled', !!(from && to)).toggleClass('opacity-50', !!(from && to));
                if (from && to) load();
            });

            $('.mode-btn').on('click', function() {
                if ($(this).prop('disabled')) return;
                mode = $(this).data('mode');
                offset = 0;
                $('#fromDate,#toDate').val('');
                $('.mode-btn,#prevBtn,#nextBtn').prop('disabled', false).removeClass('opacity-50');
                $('.mode-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
                $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
                load();
            });

            $('#prevBtn').on('click', function() {
                if (!$(this).prop('disabled')) { offset--; load(); }
            });

            $('#nextBtn').on('click', function() {
                if (!$(this).prop('disabled') && offset < 0) { offset++; load(); }
            });

            $('#filterJob').on('change', function() {
                offset = 0;
                load();
            });

            load();
        });
    </script>
@endsection