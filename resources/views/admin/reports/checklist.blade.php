@extends('admin.pages.master')
@section('title', 'Checklist Report')

@php $company = App\Models\CompanyDetails::firstOrCreate(); @endphp

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
        <div>
            <h4 class="mb-1 fw-bold"><i class="ri-checkbox-multiple-line me-2 text-info"></i>Checklist Report</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-soft-secondary btn-sm" onclick="window.print()"><i class="ri-printer-line me-1"></i>Print</button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm border-0 mb-4 no-print">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-medium small">Worker (Answered By)</label>
                    <select id="filterWorker" class="form-control select2">
                        <option value="">All Workers</option>
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
                    <div style="font-size:22px;font-weight:700;color:#1a2d52;letter-spacing:1px;">CHECKLIST REPORT</div>
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
                        <i class="ri-check-double-line text-success fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-success" id="statAnswers">—</h4>
                        <p class="text-muted mb-0 small">Total Answers</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="ri-checkbox-multiple-line text-info fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-info" id="statChecklists">—</h4>
                        <p class="text-muted mb-0 small">Checklists</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="ri-user-line text-primary fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-primary" id="statWorkers">—</h4>
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

    {{-- Checklist Answers Table --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header"><h5 class="card-title mb-0"><i class="ri-checkbox-multiple-line me-2 text-info"></i>Checklist Answers</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Worker</th>
                            <th>Job</th>
                            <th>Checklist</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th class="text-center no-print">Photo</th>
                        </tr>
                    </thead>
                    <tbody id="answersTable"><tr><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
.print-only { display:none; }
.report-table thead th { background:#1a2d52; color:#fff; border-color:#1a2d52; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.report-table tfoot td { background:#1a2d52; color:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.date-separator td { border-top:2px solid #dee2e6 !important; }
.show-at-badge-clock_in  { background:#10b981; color:#fff; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:600; }
.show-at-badge-clock_out { background:#f59e0b; color:#fff; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:600; }
.show-at-badge-both      { background:#0ea5e9; color:#fff; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:600; }
@media print {
    @page { size:A4 landscape; margin:1.2cm; }
    .no-print, .btn, button, select, input, nav, .sidebar, header { display:none !important; }
    body { background:#fff !important; font-size:10px !important; }
    .container-fluid { padding:0 !important; }
    .print-only { display:block !important; }
    .card { border:1px solid #e5e7eb !important; box-shadow:none !important; break-inside:avoid; margin-bottom:12px !important; }
    .report-table thead th { background:#1a2d52 !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .report-table tfoot td { background:#1a2d52 !important; color:#fff !important; }
    .badge, .show-at-badge-clock_in, .show-at-badge-clock_out, .show-at-badge-both { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
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
        $('#statAnswers,#statChecklists,#statWorkers,#statJobs').html('<span class="spinner-border spinner-border-sm"></span>');
        $('#answersTable').html('<tr><td colspan="9" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>');

        $.get('{{ route("reports.checklist.data") }}', params(), function(r) {
            $('#periodLabel').text(r.label);
            $('#p_period_label').text(r.label);
            $('#p_worker_label').text($('#filterWorker option:selected').text() || 'All Workers');
            $('#p_job_label').text($('#filterJob option:selected').text() || 'All Jobs');
            $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

            $('#statAnswers').text(r.total_answers);
            $('#statChecklists').text(r.unique_checklists);
            $('#statWorkers').text(r.unique_workers);
            $('#statJobs').text(r.unique_jobs);

            // Checklist answers table
            if (!r.answers || !r.answers.length) {
                $('#answersTable').html('<tr><td colspan="9" class="text-center text-muted py-4">No data for this period</td></tr>');
                return;
            }

            var html = '';
            var prevDate = null;
            r.answers.forEach(function(a, i) {
                var isNewDate = a.date !== prevDate;
                var rowClass  = isNewDate && i > 0 ? 'date-separator' : '';
                
                // Show at badge
                var showAtLabel = a.show_at === 'clock_in' ? 'Clock In' : (a.show_at === 'clock_out' ? 'Clock Out' : 'Both');
                var showAtClass = 'show-at-badge-' + a.show_at;
                
                // Answer display
                var answerHtml = '';
                if (a.type === 'photo' && a.photo) {
                    answerHtml = '<span class="badge bg-info">Photo</span>';
                } else if (a.type === 'yes_no') {
                    var ansClass = a.answer.toLowerCase() === 'yes' ? 'success' : 'danger';
                    answerHtml = '<span class="badge bg-' + ansClass + '">' + a.answer + '</span>';
                } else {
                    answerHtml = a.answer || '—';
                }
                
                html += '<tr class="' + rowClass + '">' +
                    '<td class="text-muted small">' + (i + 1) + '</td>' +
                    '<td class="small fw-semibold">' + a.date + '</td>' +
                    '<td class="small text-muted">' + a.time + '</td>' +
                    '<td>' + a.worker + '</td>' +
                    '<td><span class="fw-semibold">' + a.job + '</span><div class="text-muted small">' + a.job_id + '</div></td>' +
                    '<td><span class="fw-semibold">' + a.checklist + '</span><div><span class="' + showAtClass + '">' + showAtLabel + '</span></div></td>' +
                    '<td class="small">' + a.question + '</td>' +
                    '<td>' + answerHtml + '</td>' +
                    '<td class="text-center no-print">' + 
                        (a.photo ? '<a href="' + a.photo + '" target="_blank" class="btn btn-xs btn-soft-info"><i class="ri-image-line"></i></a>' : '—') +
                    '</td>' +
                '</tr>';
                prevDate = a.date;
            });
            $('#answersTable').html(html);

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