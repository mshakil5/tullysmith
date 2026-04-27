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
            <a id="exportBtn" href="#" class="btn btn-soft-success btn-sm"><i class="ri-download-2-line me-1"></i>Export CSV</a>
            <button class="btn btn-soft-secondary btn-sm" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Employee</label>
                    <select id="filterWorker" class="form-control select2">
                        <option value="">All Employees</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Job</label>
                    <select id="filterJob" class="form-control select2">
                        <option value="">All Jobs</option>
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}">{{ $j->job_title }} ({{ $j->job_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">From Date</label>
                    <input type="date" id="fromDate" class="form-control">
                </div>
                <div class="col-md-3">
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
        <table width="100%" style="border-bottom:2px solid #1a2d52;padding-bottom:12px;margin-bottom:16px;">
            <tr>
                <td width="70%">
                    @if($company->company_logo)
                        <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt="" height="50" style="margin-bottom:6px;"><br>
                    @endif
                    <div style="font-size:22px;font-weight:700;color:#1a2d52;letter-spacing:1px;">EXPENSE REPORT</div>
                    <div style="font-size:12px;color:#666;margin-top:2px;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                </td>
                <td width="30%" style="text-align:right;vertical-align:top;">
                    <div style="font-size:11px;color:#888;font-weight:600;">Date</div>
                    <div style="font-size:14px;font-weight:600;" id="p_period_label">—</div>
                    <div style="font-size:11px;color:#888;margin-top:6px;font-weight:600;">Worker</div>
                    <div style="font-size:13px;font-weight:600;" id="p_worker_label">All Workers</div>
                    <div style="font-size:11px;color:#888;margin-top:6px;font-weight:600;">Job</div>
                    <div style="font-size:13px;font-weight:600;" id="p_job_label">All Jobs</div>
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
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-success"></i>Expense Entries</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Worker</th>
                            <th>Job</th>
                            <th>Title</th>
                            <th class="text-end">Amount</th>
                            <th class="no-print text-center">File</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTable"><tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total Amount</td>
                            <td class="text-end fw-bold" id="footTotal">—</td>
                            <td class="no-print"></td>
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

</div>

<style>
.print-only { display:none; }
.report-table thead th { background:#1a2d52; color:#fff; border-color:#1a2d52; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.report-table tfoot td { background:#1a2d52; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.date-separator td { border-top:2px solid #dee2e6 !important; }
#totalWordsBanner { display:flex !important; }
@media print {
    @page { size:A4 portrait; margin:1.2cm; }
    .no-print, .btn, button, select, input, nav, .sidebar, header { display:none !important; }
    body { background:#fff !important; font-size:11px !important; }
    .container-fluid { padding:0 !important; }
    .print-only { display:block !important; }
    .card { border:1px solid #e5e7eb !important; box-shadow:none !important; break-inside:avoid; margin-bottom:12px !important; }
    .report-table thead th { background:#1a2d52 !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .report-table tfoot td { background:#1a2d52 !important; color:#fff !important; }
    .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
    #totalWordsBanner { display:flex !important; }
}
</style>
@endsection

@section('script')
<script>
$(function() {
    var mode = 'weekly', offset = 0;

    function params() {
        var from=$('#fromDate').val(), to=$('#toDate').val();
        return { 
            mode:(from&&to)?'custom':mode, 
            offset:(from&&to)?0:offset, 
            worker_id:$('#filterWorker').val(), 
            job_id:$('#filterJob').val(), 
            from_date:from, 
            to_date:to 
        };
    }

    function load() {
        $('#statTotal,#statCount,#statWorkers,#statJobs').html('<span class="spinner-border spinner-border-sm"></span>');
        $('#expensesTable').html('<tr><td colspan="7" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>');
        $('#footTotal').text('—');
        $('#exportBtn').attr('href', '{{ route("reports.expense.export") }}?' + $.param(params()));

        $.get('{{ route("reports.expense.data") }}', params(), function(r) {
            $('#periodLabel').text(r.label);
            $('#p_period_label').text(r.label);
            $('#p_worker_label').text($('#filterWorker option:selected').text() || 'All Workers');
            $('#p_job_label').text($('#filterJob option:selected').text() || 'All Jobs');
            $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

            $('#statTotal').text('£' + r.total_amount);
            $('#statCount').text(r.total_count);
            $('#statWorkers').text(r.unique_workers);
            $('#statJobs').text(r.unique_jobs);
            $('#footTotal').text('£' + r.total_amount);
            
            $('#totalWordsText').text(r.total_words);
            $('#totalWordsBanner').show();

            // Expense entries table
            if (!r.expenses || !r.expenses.length) {
                $('#expensesTable').html('<tr><td colspan="7" class="text-center text-muted py-4">No data for this period</td></tr>');
                return;
            }

            var html = '';
            var prevDate = null;
            r.expenses.forEach(function(e, i) {
                var isNewDate = e.date !== prevDate;
                var rowClass  = isNewDate && i > 0 ? 'date-separator' : '';
                html += '<tr class="' + rowClass + '">' +
                    '<td class="text-muted small">' + (i + 1) + '</td>' +
                    '<td class="small fw-semibold">' + (isNewDate ? e.date : '') + '</td>' +
                    '<td>' + e.worker + '</td>' +
                    '<td><span class="fw-semibold">' + e.job + '</span><div class="text-muted small">' + e.job_id + '</div></td>' +
                    '<td>' + e.title + '</td>' +
                    '<td class="text-end fw-bold text-success">£' + e.amount + '</td>' +
                    '<td class="text-center no-print">' + 
                        (e.file ? '<a href="' + e.file + '" target="_blank" class="btn btn-xs btn-soft-secondary"><i class="ri-eye-line"></i></a>' : '—') +
                    '</td>' +
                '</tr>';
                prevDate = e.date;
            });
            $('#expensesTable').html(html);

        }).fail(function() { alert('Failed to load report data.'); });
    }

    $('#fromDate,#toDate').on('change', function() {
        var from=$('#fromDate').val(), to=$('#toDate').val();
        $('.mode-btn,#prevBtn,#nextBtn').prop('disabled', !!(from&&to)).toggleClass('opacity-50', !!(from&&to));
        if (from&&to) load();
    });
    $('.mode-btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        mode=$(this).data('mode'); offset=0;
        $('#fromDate,#toDate').val('');
        $('.mode-btn,#prevBtn,#nextBtn').prop('disabled',false).removeClass('opacity-50');
        $('.mode-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
        load();
    });
    $('#prevBtn').on('click', function() { if (!$(this).prop('disabled')) { offset--; load(); } });
    $('#nextBtn').on('click', function() { if (!$(this).prop('disabled') && offset < 0) { offset++; load(); } });
    $('#filterWorker,#filterJob').on('change', function() { offset=0; load(); });

    load();
});
</script>
@endsection