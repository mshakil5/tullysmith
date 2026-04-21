@extends('admin.pages.master')
@section('title', 'Reports')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h4 class="mb-0"><i class="ri-bar-chart-2-line me-2 text-primary"></i>Reports</h4>
        <div class="d-flex gap-2">
            <a id="exportBtn" href="#" class="btn btn-soft-success btn-sm">
                <i class="ri-download-2-line me-1"></i> Export CSV
            </a>
            <button class="btn btn-soft-secondary btn-sm" onclick="printReport()">
                <i class="ri-printer-line me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-medium">Worker</label>
                    <select id="filterWorker" class="form-control select2">
                        <option value="">All Workers</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Job</label>
                    <select id="filterJob" class="form-control select2">
                        <option value="">All Jobs</option>
                        @foreach($jobs as $j)
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
                <div class="col-md-2">
                    <label class="form-label fw-medium">Mode</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary mode-btn" data-mode="daily">Daily</button>
                        <button type="button" class="btn btn-sm btn-primary mode-btn active" data-mode="weekly">Weekly</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mode-btn" data-mode="monthly">Monthly</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Period</label>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="prevBtn"><i class="ri-arrow-left-s-line"></i></button>
                        <span id="periodLabel" class="fw-semibold text-center flex-grow-1 small"></span>
                        <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="nextBtn"><i class="ri-arrow-right-s-line"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="printArea">
        <div class="print-only mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <i class="ri-bar-chart-fill text-primary fs-1 me-2"></i>
                    <h1 class="fw-bold mb-0" style="letter-spacing: 1px; color: #1a2d52;">REPORTS</h1>
                </div>
                <div class="text-muted small"><i class="ri-printer-line"></i> Printed on: {{ now()->format('M d, Y H:i') }}</div>
            </div>
            
            <div class="border rounded p-3 bg-light" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div>
                    <div class="small text-muted"><i class="ri-user-line"></i> Worker</div>
                    <div class="fw-bold" id="p_worker">All Workers</div>
                    <div class="small text-muted mt-2"><i class="ri-briefcase-line"></i> Job</div>
                    <div class="fw-bold" id="p_job">All Jobs</div>
                </div>
                <div>
                    <div class="small text-muted"><i class="ri-calendar-line"></i> From Date</div>
                    <div class="fw-bold" id="p_from">---</div>
                    <div class="small text-muted mt-2"><i class="ri-calendar-event-line"></i> To Date</div>
                    <div class="fw-bold" id="p_to">---</div>
                </div>
                <div>
                    <div class="small text-muted"><i class="ri-layout-grid-line"></i> Mode</div>
                    <span class="badge bg-dark" id="p_mode">Weekly</span>
                    <div class="small text-muted mt-2"><i class="ri-time-line"></i> Period</div>
                    <div class="fw-bold" id="p_period">---</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold mb-0 text-primary" id="statHours">—</h3>
                    <p class="text-muted mb-0 small">Total Hours</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold mb-0 text-success" id="statSessions">—</h3>
                    <p class="text-muted mb-0 small">Sessions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold mb-0 text-info" id="statWorkers">—</h3>
                    <p class="text-muted mb-0 small">Workers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-3">
                    <h3 class="fw-bold mb-0 text-warning" id="statJobs">—</h3>
                    <p class="text-muted mb-0 small">Jobs</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h5 class="card-title mb-0">By Worker</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Worker</th>
                                    <th class="text-center">Hours</th>
                                    <th class="text-center">Sessions</th>
                                    <th class="text-center">Jobs</th>
                                </tr>
                            </thead>
                            <tbody id="workerTable">
                                <tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h5 class="card-title mb-0">By Job</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Job</th>
                                    <th class="text-center">Hours</th>
                                    <th class="text-center">Sessions</th>
                                    <th class="text-center">Workers</th>
                                </tr>
                            </thead>
                            <tbody id="jobTable">
                                <tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="jobDetailsContainer"></div>

    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Hours Overview</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-xs btn-soft-primary graph-type-btn active" data-type="bar">Bar</button>
                <button class="btn btn-xs btn-soft-secondary graph-type-btn" data-type="line">Line</button>
            </div>
        </div>
        <div class="card-body">
            <div id="chartEmpty" class="text-center py-5 text-muted d-none">
                <i class="ri-bar-chart-line ri-2x mb-2 d-block opacity-50"></i> No data for this period
            </div>
            <canvas id="reportChart" height="100"></canvas>
        </div>
    </div>

</div>

<style>
    /* UI Only */
    .print-only { display: none; }

    @media print {
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: portrait; margin: 1cm; }
        
        .no-print, .btn, button, select, input, .card-header button { display: none !important; }
        
        body { background: white !important; font-size: 11px !important; }
        .container-fluid { padding: 0 !important; }
        
        /* Matching the Dark Table Headers in Image */
        .table thead th { 
            background-color: #1a2d52 !important; 
            color: white !important; 
            border: 1px solid #1a2d52 !important;
            text-align: center;
        }

        /* Layout Grid Fix */
        .row { display: flex !important; flex-wrap: nowrap !important; }
        .col-md-3 { width: 25% !important; }
        .card { border: 1px solid #eef2f7 !important; box-shadow: none !important; margin-bottom: 15px !important; }
        
        /* Show Print Header */
        .print-only { display: block !important; }
    }
</style>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function () {

    var mode          = 'weekly';
    var offset        = 0;
    var chartInstance = null;
    var chartType     = 'bar';
    var lastRes       = null;

    function getParams() {
        var from = $('#fromDate').val();
        var to   = $('#toDate').val();
        return {
            mode:      (from && to) ? 'custom' : mode,
            offset:    (from && to) ? 0 : offset,
            worker_id: $('#filterWorker').val(),
            job_id:    $('#filterJob').val(),
            from_date: from,
            to_date:   to,
        };
    }

    function updateExportBtn() {
        $('#exportBtn').attr('href', "{{ route('reports.export') }}?" + $.param(getParams()));
    }

    function loadData() {
        $('#statHours, #statSessions, #statWorkers, #statJobs').html('<span class="spinner-border spinner-border-sm"></span>');
        $('#workerTable, #jobTable').html('<tr><td colspan="4" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm"></span></td></tr>');
        $('#jobDetailsContainer').html('');
        updateExportBtn();

        $.get("{{ route('reports.data') }}", getParams(), function (res) {
            lastRes = res;

            $('#periodLabel').text(res.label);
            $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

            $('#statHours').text(res.total_hours + 'h');
            $('#statSessions').text(res.total_sessions);
            $('#statWorkers').text(res.unique_workers);
            $('#statJobs').text(res.unique_jobs);
            $('#p_worker').text($('#filterWorker option:selected').text());
            $('#p_job').text($('#filterJob option:selected').text());
            $('#p_from').text(res.start_date || $('#fromDate').val() || '—');
            $('#p_to').text(res.end_date || $('#toDate').val() || '—');
            $('#p_period').text(res.label);
            $('#p_mode').text(mode.charAt(0).toUpperCase() + mode.slice(1));

            var hasData = res.graph_hours.some(function(h) { return h > 0; });
            if (!hasData) {
                $('#reportChart').hide();
                $('#chartEmpty').removeClass('d-none');
            } else {
                $('#reportChart').show();
                $('#chartEmpty').addClass('d-none');
                renderChart(res.graph_labels, res.graph_hours, res.graph_sessions);
            }

            if (!res.worker_breakdown.length) {
                $('#workerTable').html('<tr><td colspan="4" class="text-center text-muted py-4">No data</td></tr>');
            } else {
                var wHtml = '';
                res.worker_breakdown.forEach(function(w) {
                    wHtml += '<tr>' +
                        '<td><strong>' + w.name + '</strong></td>' +
                        '<td class="text-center"><span class="badge bg-primary">' + w.hours + 'h</span></td>' +
                        '<td class="text-center">' + w.sessions + '</td>' +
                        '<td class="text-center">' + w.jobs + '</td>' +
                    '</tr>';
                });
                $('#workerTable').html(wHtml);
            }

            if (!res.job_breakdown.length) {
                $('#jobTable').html('<tr><td colspan="4" class="text-center text-muted py-4">No data</td></tr>');
                return;
            }

            var jHtml = '';
            res.job_breakdown.forEach(function(j) {
                jHtml += '<tr>' +
                    '<td><strong>' + j.title + '</strong><div class="text-muted small">' + j.job_id + '</div></td>' +
                    '<td class="text-center"><span class="badge bg-success">' + j.hours + 'h</span></td>' +
                    '<td class="text-center">' + j.sessions + '</td>' +
                    '<td class="text-center">' + j.workers + '</td>' +
                '</tr>';
            });
            $('#jobTable').html(jHtml);

            renderJobDetails(res.job_breakdown);

        }).fail(function() {
            showError('Failed to load report data.');
        });
    }

    function renderJobDetails(jobs) {
        var $container = $('#jobDetailsContainer');
        $container.empty();

        jobs.forEach(function(j, idx) {
            var hasExpenses   = j.expenses   && j.expenses.length > 0;
            var hasChecklists = j.checklists && j.checklists.length > 0;
            if (!hasExpenses && !hasChecklists) return;

            var $card = $('<div class="card border-0 shadow-sm mb-3"></div>');

            $card.append(
                '<div class="card-header d-flex justify-content-between align-items-center py-2">' +
                    '<h6 class="mb-0 fw-semibold"><i class="ri-briefcase-line me-2 text-primary"></i>' + j.title +
                        ' <span class="text-muted fw-normal small">(' + j.job_id + ')</span></h6>' +
                    '<div class="d-flex gap-2">' +
                        '<span class="badge bg-primary">' + j.hours + 'h</span>' +
                        '<span class="badge bg-success">£' + j.expense_total + '</span>' +
                    '</div>' +
                '</div>'
            );

            var $body = $('<div class="card-body p-0"></div>');

            if (hasExpenses) {
                var $expWrap = $('<div class="p-3 border-bottom"></div>');
                $expWrap.append('<h6 class="text-muted mb-2 small text-uppercase fw-semibold">Expenses</h6>');

                var $tbl = $('<table class="table table-sm table-bordered mb-0"></table>');
                $tbl.append(
                    '<thead class="table-light"><tr>' +
                        '<th>Type</th><th>Title</th><th>Amount</th><th>Date</th>' +
                        '<th class="no-print">File</th>' +
                    '</tr></thead>'
                );
                var $tbody = $('<tbody></tbody>');
                j.expenses.forEach(function(e) {
                    var badgeColor = e.type === 'Invoice' ? 'primary' : 'info';
                    var $tr = $('<tr></tr>');
                    $tr.append('<td><span class="badge bg-' + badgeColor + '">' + e.type + '</span></td>');
                    $tr.append('<td>' + e.title + '</td>');
                    $tr.append('<td><strong class="text-success">£' + e.amount + '</strong></td>');
                    $tr.append('<td>' + e.invoice_date + '</td>');
                    var $fileCell = $('<td class="no-print"></td>');
                    $fileCell.append('<a href="' + e.file + '" target="_blank" class="btn btn-xs btn-soft-secondary"><i class="ri-eye-line"></i></a>');
                    $tr.append($fileCell);
                    $tbody.append($tr);
                });
                $tbl.append($tbody);
                $expWrap.append($tbl);
                $body.append($expWrap);
            }

            if (hasChecklists) {
                var $clWrap = $('<div class="p-3"></div>');
                $clWrap.append('<h6 class="text-muted mb-2 small text-uppercase fw-semibold">Checklists (Approved)</h6>');

                j.checklists.forEach(function(c, ci) {
                    var showAtBadge = c.show_at === 'clock_in'
                        ? '<span class="badge bg-success">Clock In</span>'
                        : c.show_at === 'clock_out'
                            ? '<span class="badge bg-warning text-dark">Clock Out</span>'
                            : '<span class="badge bg-info">Both</span>';

                    var accordionId = 'jcl_' + idx + '_' + ci;

                    var $acc = $('<div class="accordion mb-2 no-print"></div>');
                    var $item = $('<div class="accordion-item"></div>');
                    $item.append(
                        '<h2 class="accordion-header">' +
                            '<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#' + accordionId + '">' +
                                '<strong>' + c.title + '</strong>' +
                                '<span class="ms-2">' + showAtBadge + '</span>' +
                                '<span class="badge bg-success ms-2">' + c.answered + ' answered</span>' +
                            '</button>' +
                        '</h2>'
                    );

                    var $collapse = $('<div id="' + accordionId + '" class="accordion-collapse collapse"></div>');
                    var $accBody  = $('<div class="accordion-body p-2"></div>');

                    if (c.answers.length) {
                        var $aTbl  = $('<table class="table table-sm table-bordered mb-0"></table>');
                        $aTbl.append(
                            '<thead class="table-light"><tr>' +
                                '<th>Question</th><th>Answer</th><th>Photo</th><th>By</th><th>At</th>' +
                            '</tr></thead>'
                        );
                        var $aTbody = $('<tbody></tbody>');
                        c.answers.forEach(function(a) {
                            var photoHtml = a.photo
                                ? '<img src="' + a.photo + '" style="height:40px;object-fit:cover;border-radius:4px;">'
                                : '—';
                            var $atr = $('<tr></tr>');
                            $atr.append('<td>' + a.question + '</td>');
                            $atr.append('<td>' + (a.answer ?? '—') + '</td>');
                            $atr.append('<td>' + photoHtml + '</td>');
                            $atr.append('<td>' + a.answered_by + '</td>');
                            $atr.append('<td>' + a.answered_at + '</td>');
                            $aTbody.append($atr);
                        });
                        $aTbl.append($aTbody);
                        $accBody.append($aTbl);
                    } else {
                        $accBody.append('<p class="text-muted mb-0">No answers yet</p>');
                    }

                    $collapse.append($accBody);
                    $item.append($collapse);
                    $acc.append($item);
                    $clWrap.append($acc);

                    var $printBlock = $('<div class="print-only mb-3"></div>');
                    $printBlock.append('<p class="mb-1"><strong>' + c.title + '</strong> ' + showAtBadge + ' &nbsp; ' + c.answered + ' answered</p>');
                    if (c.answers.length) {
                        var $pTbl   = $('<table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:8px;"></table>');
                        var thStyle = 'border:1px solid #ccc;padding:4px 6px;background:#f3f4f6;';
                        var tdStyle = 'border:1px solid #ccc;padding:4px 6px;';
                        $pTbl.append(
                            '<thead><tr>' +
                                '<th style="' + thStyle + '">Question</th>' +
                                '<th style="' + thStyle + '">Answer</th>' +
                                '<th style="' + thStyle + '">By</th>' +
                                '<th style="' + thStyle + '">At</th>' +
                            '</tr></thead>'
                        );
                        var $pBody = $('<tbody></tbody>');
                        c.answers.forEach(function(a) {
                            $pBody.append(
                                '<tr>' +
                                    '<td style="' + tdStyle + '">' + a.question + '</td>' +
                                    '<td style="' + tdStyle + '">' + (a.answer ?? '—') + '</td>' +
                                    '<td style="' + tdStyle + '">' + a.answered_by + '</td>' +
                                    '<td style="' + tdStyle + '">' + a.answered_at + '</td>' +
                                '</tr>'
                            );
                        });
                        $pTbl.append($pBody);
                        $printBlock.append($pTbl);
                    }
                    $clWrap.append($printBlock);
                });

                $body.append($clWrap);
            }

            $card.append($body);
            $container.append($card);
        });
    }

    function renderChart(labels, hours, sessions) {
        if (chartInstance) chartInstance.destroy();
        var ctx = document.getElementById('reportChart').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: chartType,
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Hours Worked',
                        data: hours,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 2,
                        borderRadius: chartType === 'bar' ? 4 : 0,
                        tension: 0.4,
                        fill: chartType === 'line',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Sessions',
                        data: sessions,
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 2,
                        borderRadius: chartType === 'bar' ? 4 : 0,
                        tension: 0.4,
                        fill: false,
                        type: chartType === 'bar' ? 'bar' : 'line',
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.label === 'Hours Worked') return ' ' + ctx.raw + 'h';
                                return ' ' + ctx.raw + ' sessions';
                            }
                        }
                    }
                },
                scales: {
                    y:  { type: 'linear', display: true, position: 'left',  title: { display: true, text: 'Hours' },   beginAtZero: true },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Sessions' }, beginAtZero: true, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    $('#fromDate, #toDate').on('change', function() {
        var from = $('#fromDate').val();
        var to   = $('#toDate').val();
        if (from && to) {
            $('.mode-btn, #prevBtn, #nextBtn').prop('disabled', true).addClass('opacity-50');
        } else {
            $('.mode-btn, #prevBtn, #nextBtn').prop('disabled', false).removeClass('opacity-50');
        }
        if (from && to) loadData();
    });

    $('.mode-btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        mode = $(this).data('mode');
        offset = 0;
        $('#fromDate, #toDate').val('');
        $('.mode-btn, #prevBtn, #nextBtn').prop('disabled', false).removeClass('opacity-50');
        $('.mode-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
        loadData();
    });

    $('#prevBtn').on('click', function() { if (!$(this).prop('disabled')) { offset--; loadData(); } });
    $('#nextBtn').on('click', function() { if (!$(this).prop('disabled') && offset < 0) { offset++; loadData(); } });

    $('#filterWorker, #filterJob').on('change', function() { offset = 0; loadData(); });

    $('.graph-type-btn').on('click', function() {
        chartType = $(this).data('type');
        $('.graph-type-btn').removeClass('btn-soft-primary active').addClass('btn-soft-secondary');
        $(this).removeClass('btn-soft-secondary').addClass('btn-soft-primary active');
        loadData();
    });

    loadData();
});

function printReport() {
    document.body.classList.add('printing');
    window.print();
    document.body.classList.remove('printing');
}
</script>
@endsection