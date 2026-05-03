@extends('admin.pages.master')
@section('title', 'Time Report')

@php $company = App\Models\CompanyDetails::firstOrCreate(); @endphp

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
            <div>
                <h4 class="mb-1 fw-bold"><i class="ri-time-line me-2 text-primary"></i>Time Report</h4>
            </div>
            <div class="d-flex gap-2">
                <a id="exportBtn" href="#" class="btn btn-soft-success btn-sm"><i
                        class="ri-download-2-line me-1"></i>Export CSV</a>
                <button class="btn btn-soft-primary btn-sm" id="printBtn">
                    <i class="ri-file-pdf-line me-1"></i>PDF
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-medium small">Worker</label>
                        <select id="filterWorker" class="form-control select2">
                            <option value="">All Workers</option>
                            @foreach ($workers as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium small">Job Name</label>
                        <select id="filterJob" class="form-control select2">
                            <option value="">All Jobs</option>
                            @foreach ($jobs as $j)
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
                            <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="prevBtn"><i
                                    class="ri-arrow-left-s-line"></i></button>
                            <span id="periodLabel" class="fw-semibold text-center flex-grow-1 small text-truncate"></span>
                            <button class="btn btn-soft-secondary btn-sm btn-icon rounded-circle" id="nextBtn"><i
                                    class="ri-arrow-right-s-line"></i></button>
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
                            <img src="{{ asset('uploads/company/' . $company->company_logo) }}" alt=""
                                height="50" style="margin-bottom:6px;"><br>
                        @endif
                        <div style="font-size:22px;font-weight:700;color:#1a2d52;letter-spacing:1px; line-height: 1.2;">TIME
                            REPORT</div>
                        <div style="font-size:12px;color:#666;margin-top:2px;">Generated:
                            {{ now()->format('d M Y, h:i A') }}</div>
                    </td>

                    <td width="30%" class="print-header-box" style="text-align:right; vertical-align: bottom;">
                        <div style="display: inline-block; text-align: left; padding-bottom: 4px;">
                            <div class="mb-1">
                                <span class="small fw-semibold" style="min-width: 55px; display: inline-block;">Date:</span>
                                <span class="small fw-semibold" id="p_period_label">—</span>
                            </div>

                            <div class="mb-1">
                                <span class="small fw-semibold"
                                    style="min-width: 55px; display: inline-block;">Worker:</span>
                                <span class="small fw-semibold" id="p_worker_label">All Workers</span>
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
        <div class="row g-3 mb-4 no-print" id="summaryCards">
            @foreach ([['statHours', 'Total Hours', 'text-primary', 'ri-time-line'], ['statSessions', 'Sessions', 'text-success', 'ri-calendar-check-line'], ['statWorkers', 'Workers', 'text-info', 'ri-user-line'], ['statJobs', 'Jobs', 'text-warning', 'ri-briefcase-line']] as [$id, $label, $cls, $icon])
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div
                                class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                <i class="{{ $icon }} {{ $cls }} fs-5"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold {{ $cls }}" id="{{ $id }}">—</h4>
                                <p class="text-muted mb-0 small">{{ $label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Chronological Entries Table --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-list-check me-2 text-primary"></i>Time Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 report-table">
                        <thead id="logsHead"></thead>
                        <tbody id="logsTable">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Loading...</td>
                            </tr>
                        </tbody>
                        <tfoot id="logsFoot"></tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Chart --}}
        <div class="card border-0 shadow-sm mb-4 no-print">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Hours Overview</h5>
                <div class="d-flex gap-1">
                    <button class="btn btn-xs btn-soft-primary graph-type-btn active" data-type="bar">Bar</button>
                    <button class="btn btn-xs btn-soft-secondary graph-type-btn" data-type="line">Line</button>
                </div>
            </div>
            <div class="card-body">
                <div id="chartEmpty" class="text-center py-5 text-muted d-none">
                    <i class="ri-bar-chart-line ri-2x mb-2 d-block opacity-50"></i>No data for this period
                </div>
                <canvas id="reportChart" height="90"></canvas>
            </div>
        </div>

    </div>

    <style>
        .print-only {
            display: none;
        }

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

        .date-separator td {
            border-top: 2px solid #dee2e6 !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 1.2cm;
            }

            .no-print,
            .btn,
            button,
            select,
            input,
            nav,
            .sidebar,
            header {
                display: none !important;
            }

            body {
                background: #fff !important;
                font-size: 11px !important;
            }

            .container-fluid {
                padding: 0 !important;
            }

            .print-only {
                display: block !important;
            }

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

            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        .print-header-box {
            text-align: right;
        }

        .print-header-title {
            font-size: 14px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            display: inline-block;
            padding-bottom: 2px;
        }

        .print-header-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a2d52;
            margin-top: 2px;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function() {

            $('#printBtn').on('click', function() {
                window.open('{{ route('reports.time.pdf') }}?' + $.param(params()), '_blank');
            });

            var mode = 'weekly',
                offset = 0,
                chartInst = null,
                chartType = 'bar';

            function params() {
                var from = $('#fromDate').val(),
                    to = $('#toDate').val();
                return {
                    mode: (from && to) ? 'custom' : mode,
                    offset: (from && to) ? 0 : offset,
                    worker_id: $('#filterWorker').val(),
                    job_id: $('#filterJob').val(),
                    from_date: from,
                    to_date: to,
                };
            }

            function load() {
                $('#statHours,#statSessions,#statWorkers,#statJobs').html(
                    '<span class="spinner-border spinner-border-sm"></span>');
                $('#logsTable').html(
                    '<tr><td colspan="7" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></td></tr>'
                );
                $('#logsHead').html('');
                $('#logsFoot').html('');
                $('#exportBtn').attr('href', '{{ route('reports.time.export') }}?' + $.param(params()));

                $.get('{{ route('reports.time.data') }}', params(), function(r) {
                    $('#periodLabel').text(r.label);
                    $('#p_period_label').text(r.label);
                    $('#p_worker_label').text($('#filterWorker option:selected').text() || 'All Workers');
                    $('#p_job_label').text($('#filterJob option:selected').text() || 'All Jobs');
                    $('#nextBtn').toggleClass('disabled opacity-50', offset >= 0);

                    $('#statHours').text(r.total_hours + 'h');
                    $('#statSessions').text(r.total_sessions);
                    $('#statWorkers').text(r.unique_workers);
                    $('#statJobs').text(r.unique_jobs);

                    // Chart
                    var hasData = r.graph_hours && r.graph_hours.some(function(h) {
                        return h > 0;
                    });
                    if (!hasData || !r.graph_labels.length) {
                        $('#reportChart').hide();
                        $('#chartEmpty').removeClass('d-none');
                    } else {
                        $('#reportChart').show();
                        $('#chartEmpty').addClass('d-none');
                        renderChart(r.graph_labels, r.graph_hours, r.graph_sessions);
                    }

                    // Dynamic columns
                    var isWorkerFiltered = $('#filterWorker').val() !== '';
                    var isJobFiltered = $('#filterJob').val() !== '';
                    var totalCols = 5 + (!isWorkerFiltered ? 1 : 0) + (!isJobFiltered ? 1 : 0);

                    // thead
                    var theadHtml = '<tr><th>#</th><th>Date</th>';
                    if (!isWorkerFiltered) theadHtml += '<th>Worker</th>';
                    if (!isJobFiltered) theadHtml += '<th>Job</th>';
                    theadHtml +=
                        '<th class="text-center">Clock In</th><th class="text-center">Clock Out</th><th class="text-end">Hours</th></tr>';
                    $('#logsHead').html(theadHtml);

                    // tfoot
                    $('#logsFoot').html(
                        '<tr><td colspan="' + (totalCols - 1) +
                        '" class="text-end fw-bold">Total Hours</td>' +
                        '<td class="text-end fw-bold">' + r.total_hours + 'h</td></tr>'
                    );

                    // tbody
                    if (!r.logs || !r.logs.length) {
                        $('#logsTable').html('<tr><td colspan="' + totalCols +
                            '" class="text-center text-muted py-4">No data for this period</td></tr>');
                        return;
                    }

                    var html = '';
                    var prevDate = null;
                    r.logs.forEach(function(l, i) {
                        var isNewDate = l.date !== prevDate;
                        var rowClass = isNewDate && i > 0 ? 'date-separator' : '';
                        html += '<tr class="' + rowClass + '">' +
                            '<td class="text-muted small">' + (i + 1) + '</td>' +
                            '<td class="small fw-semibold">' + (isNewDate ? l.date : '') + '</td>';
                        if (!isWorkerFiltered) html += '<td>' + l.worker + '</td>';
                        if (!isJobFiltered) html += '<td><span class="fw-semibold">' + l.job +
                            '</span><div class="text-muted small">' + l.job_id + '</div></td>';
                        html += '<td class="text-center small">' + l.clock_in + '</td>' +
                            '<td class="text-center small">' + (l.clock_out === 'Active' ?
                                '<span class="badge bg-warning text-dark">Active</span>' : l
                                .clock_out) + '</td>' +
                            '<td class="text-end fw-bold text-primary">' + l.hours + 'h</td>' +
                            '</tr>';
                        prevDate = l.date;
                    });
                    $('#logsTable').html(html);

                }).fail(function() {
                    alert('Failed to load report data.');
                });
            }

            function renderChart(labels, hours, sessions) {
                if (chartInst) chartInst.destroy();
                var ctx = document.getElementById('reportChart').getContext('2d');
                chartInst = new Chart(ctx, {
                    type: chartType,
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Hours',
                                data: hours,
                                backgroundColor: 'rgba(99,102,241,.7)',
                                borderColor: 'rgba(99,102,241,1)',
                                borderWidth: 2,
                                borderRadius: chartType === 'bar' ? 4 : 0,
                                tension: .4,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Sessions',
                                data: sessions,
                                type: chartType === 'bar' ? 'bar' : 'line',
                                backgroundColor: 'rgba(34,197,94,.5)',
                                borderColor: 'rgba(34,197,94,1)',
                                borderWidth: 2,
                                borderRadius: chartType === 'bar' ? 4 : 0,
                                tension: .4,
                                fill: false,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Hours'
                                },
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Sessions'
                                },
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
            }

            $('#fromDate,#toDate').on('change', function() {
                var from = $('#fromDate').val(),
                    to = $('#toDate').val();
                $('.mode-btn,#prevBtn,#nextBtn').prop('disabled', !!(from && to)).toggleClass('opacity-50',
                    !!(from && to));
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
                if (!$(this).prop('disabled')) {
                    offset--;
                    load();
                }
            });
            $('#nextBtn').on('click', function() {
                if (!$(this).prop('disabled') && offset < 0) {
                    offset++;
                    load();
                }
            });
            $('#filterWorker,#filterJob').on('change', function() {
                offset = 0;
                load();
            });
            $('.graph-type-btn').on('click', function() {
                chartType = $(this).data('type');
                $('.graph-type-btn').removeClass('btn-soft-primary active').addClass('btn-soft-secondary');
                $(this).removeClass('btn-soft-secondary').addClass('btn-soft-primary active');
                load();
            });

            load();
        });
    </script>
@endsection
