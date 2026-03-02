@extends('admin.pages.master')
@section('title', 'Reports')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h4 class="mb-0"><i class="ri-bar-chart-2-line me-2 text-primary"></i>Reports</h4>
        <a id="exportBtn" href="#" class="btn btn-soft-success btn-sm">
            <i class="ri-download-2-line me-1"></i> Export CSV
        </a>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-medium">Worker</label>
                    <select id="filterWorker" class="form-control select2">
                        <option value="">All Workers</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Job</label>
                    <select id="filterJob" class="form-control select2">
                        <option value="">All Jobs</option>
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}">{{ $j->job_title }} ({{ $j->job_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Mode</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary mode-btn" data-mode="daily">Daily</button>
                        <button type="button" class="btn btn-sm btn-primary mode-btn active" data-mode="weekly">Weekly</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mode-btn" data-mode="monthly">Monthly</button>
                    </div>
                </div>
                <div class="col-md-3">
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

    {{-- Stats Cards --}}
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

    {{-- Graph --}}
    <div class="card border-0 shadow-sm mb-4">
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

    {{-- Breakdowns --}}
    <div class="row g-4">
        <div class="col-md-6">
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
        <div class="col-md-6">
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

</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function () {

    var mode    = 'weekly';
    var offset  = 0;
    var chartInstance = null;
    var chartType = 'bar';

    function getParams() {
        return {
            mode:      mode,
            offset:    offset,
            worker_id: $('#filterWorker').val(),
            job_id:    $('#filterJob').val(),
        };
    }

    function updateExportBtn() {
        var p = getParams();
        var qs = $.param(p);
        $('#exportBtn').attr('href', "{{ route('reports.export') }}?" + qs);
    }

    function loadData() {
        $('#statHours, #statSessions, #statWorkers, #statJobs').html('<span class="spinner-border spinner-border-sm"></span>');
        $('#workerTable, #jobTable').html('<tr><td colspan="4" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm"></span></td></tr>');
        updateExportBtn();

        $.get("{{ route('reports.data') }}", getParams(), function (res) {

            $('#periodLabel').text(res.label);

            // Next btn disable if at 0
            $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

            // Stats
            $('#statHours').text(res.total_hours + 'h');
            $('#statSessions').text(res.total_sessions);
            $('#statWorkers').text(res.unique_workers);
            $('#statJobs').text(res.unique_jobs);

            // Chart
            var hasData = res.graph_hours.some(function(h) { return h > 0; });
            if (!hasData) {
                $('#reportChart').hide();
                $('#chartEmpty').removeClass('d-none');
            } else {
                $('#reportChart').show();
                $('#chartEmpty').addClass('d-none');
                renderChart(res.graph_labels, res.graph_hours, res.graph_sessions);
            }

            // Worker table
            if (res.worker_breakdown.length === 0) {
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

            // Job table
            if (res.job_breakdown.length === 0) {
                $('#jobTable').html('<tr><td colspan="4" class="text-center text-muted py-4">No data</td></tr>');
            } else {
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
            }

        }).fail(function() {
            showError('Failed to load report data.');
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
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Hours' },
                        beginAtZero: true,
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Sessions' },
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                    }
                }
            }
        });
    }

    // Mode buttons
    $('.mode-btn').on('click', function() {
        mode = $(this).data('mode');
        offset = 0;
        $('.mode-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
        loadData();
    });

    // Prev / Next
    $('#prevBtn').on('click', function() { offset--; loadData(); });
    $('#nextBtn').on('click', function() { if (offset < 0) { offset++; loadData(); } });

    // Filters
    $('#filterWorker, #filterJob').on('change', function() { offset = 0; loadData(); });

    // Chart type toggle
    $('.graph-type-btn').on('click', function() {
        chartType = $(this).data('type');
        $('.graph-type-btn').removeClass('btn-soft-primary active').addClass('btn-soft-secondary');
        $(this).removeClass('btn-soft-secondary').addClass('btn-soft-primary active');
        loadData();
    });

    // Init
    loadData();
});
</script>
@endsection