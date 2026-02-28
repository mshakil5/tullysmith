@extends('admin.pages.master')
@section('title', 'Dashboard')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css" rel="stylesheet">
<style>
    .fc-event { cursor:pointer;}
    .fc-event:hover {
        background-color: inherit !important;
        border-color: inherit !important;
    }
</style>
@endpush

@section('content')

@hasanyrole('Super Admin|Admin')
<div class="container-fluid mb-3 d-flex justify-content-between align-items-center">
    <h4>Dashboard</h4>
    <a href="{{ route('jobAssignment.index') }}" class="btn btn-primary">Assign Job</a>
</div>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Workers</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $totalWorker }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-primary rounded fs-3"><i class="bx bx-user text-primary"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Active Jobs</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $activeJobs }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-success rounded fs-3"><i class="bx bx-briefcase-alt-2 text-success"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Pending Jobs</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $pendingJobs }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-warning rounded fs-3"><i class="bx bx-time-five text-warning"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Today's Assignments</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $todaysAssignments }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-info rounded fs-3"><i class="bx bx-calendar-check text-info"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>
@endhasanyrole

@role('Worker')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Assignments</h5>
            <span class="badge bg-primary">{{ $myAssignments->count() }} Total</span>
        </div>
        <div class="card-body">
            @php
                $todayStr  = now()->toDateString();
                $todayJobs = $myAssignments->filter(fn($a) => $a['assigned_date'] === $todayStr)->values();
            @endphp
            <div class="mb-4 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold text-uppercase" style="font-size:0.8rem;letter-spacing:0.05em;">{{ now()->format('l, M d') }}</span>
                    <span class="text-muted small">{{ $todayJobs->count() }} {{ Str::plural('job', $todayJobs->count()) }}</span>
                </div>
                @if($todayJobs->isEmpty())
                    <p class="text-muted text-center mb-0 py-2" style="font-size:0.85rem;">No jobs scheduled for today</p>
                @else
                    @foreach($todayJobs as $tj)
                    <a href="{{ route('serviceJob.show', $tj['service_job_id']) }}" class="text-decoration-none">
                        <div class="d-flex justify-content-between align-items-start p-2 rounded mb-2" style="background:#fff;border:1px solid #e2e8f0;">
                            <div>
                                <p class="mb-0 fw-semibold text-dark" style="font-size:0.88rem;">{{ $tj['job_title'] }}</p>
                                <p class="mb-0 text-muted" style="font-size:0.78rem;">{{ $tj['job_id'] }} · {{ $tj['client_name'] }}</p>
                                @if($tj['address'])<p class="mb-0 text-muted" style="font-size:0.75rem;"><i class="ri-map-pin-line me-1"></i>{{ $tj['address'] }}</p>@endif
                            </div>
                            <div class="text-end ms-2">
                                @if($tj['start_time'])
                                <span class="badge bg-light text-dark" style="font-size:0.7rem;">
                                    {{ \Carbon\Carbon::parse($tj['start_time'])->format('h:i A') }}
                                    @if($tj['end_time']) — {{ \Carbon\Carbon::parse($tj['end_time'])->format('h:i A') }} @endif
                                </span><br>
                                @endif
                                <span class="badge bg-{{ $tj['status'] === 'active' ? 'success' : ($tj['status'] === 'pending' ? 'warning' : 'secondary') }} mt-1" style="font-size:0.68rem;">{{ ucfirst($tj['status']) }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                @endif
            </div>
            <div id="myCalendar"></div>
        </div>
    </div>
</div>
@endrole

{{-- Shared Modal --}}
<div id="myAssignmentModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="btn-close float-end" data-bs-dismiss="modal"></button>
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <p class="fw-bold fs-6 mb-0" id="myModalJobTitle"></p>
                        <p class="text-muted small mb-0" id="myModalJobId"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Worker</p>
                        <p class="fw-semibold mb-0" id="myModalWorker"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Client</p>
                        <p class="fw-semibold mb-0" id="myModalClient"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Date</p>
                        <p class="fw-semibold mb-0" id="myModalDate"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Time</p>
                        <p class="fw-semibold mb-0" id="myModalTime"></p>
                    </div>
                    <div class="col-12">
                        <p class="text-muted mb-1 small">Address</p>
                        <p class="fw-semibold mb-0" id="myModalAddress"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Status</p>
                        <p class="mb-0" id="myModalStatus"></p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 small">Priority</p>
                        <p class="mb-0" id="myModalPriority"></p>
                    </div>
                    <div class="col-12" id="myModalNoteRow" style="display:none;">
                        <p class="text-muted mb-1 small">Note</p>
                        <p class="fw-semibold mb-0" id="myModalNote"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary btn-sm" id="myModalViewBtn">View Job</a>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
<script>
$(function () {

    function formatTime12h(start, end) {
        let fmt = t => t ? new Date('1970-01-01T' + t).toLocaleTimeString([], { hour:'2-digit', minute:'2-digit', hour12:true }) : '';
        return start ? fmt(start) + (end ? ' — ' + fmt(end) : '') : '-';
    }

    function formatDate(d) {
        return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' });
    }

    function statusBadge(val, map) {
        if (!val) return '-';
        let color = map[val] ?? 'secondary';
        let textClass = color === 'warning' ? 'text-dark' : '';
        return `<span class="badge bg-${color} ${textClass}">${val.charAt(0).toUpperCase() + val.slice(1)}</span>`;
    }

    function openModal(p) {
        $('#myModalJobTitle').text(p.job_title ?? '');
        $('#myModalJobId').text(p.job_id ?? '');
        $('#myModalWorker').text(p.worker_name ?? '-');
        $('#myModalClient').text(p.client_name ?? '-');
        $('#myModalDate').text(p.assigned_date ? formatDate(p.assigned_date) : '-');
        $('#myModalTime').text(formatTime12h(p.start_time, p.end_time));
        $('#myModalAddress').text(p.address || '-');
        $('#myModalStatus').html(statusBadge(p.status, { active:'success', pending:'warning', draft:'secondary', completed:'primary' }));
        $('#myModalPriority').html(statusBadge(p.priority, { low:'success', medium:'warning', high:'danger', urgent:'danger' }));
        p.note ? $('#myModalNote').text(p.note) && $('#myModalNoteRow').show() : $('#myModalNoteRow').hide();
        $('#myModalViewBtn').attr('href', '/admin/service-job/' + p.service_job_id);
        $('#myAssignmentModal').modal('show');
    }

    if (document.getElementById('calendar')) {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            headerToolbar: { left:'prev', center:'title', right:'today next' },
            height: 'auto',
            events: @json($assignments ?? []),
            eventClick: function(info) { openModal(info.event.extendedProps); }
        });
        calendar.render();
    }

    if (document.getElementById('myCalendar')) {
        var myCalendar = new FullCalendar.Calendar(document.getElementById('myCalendar'), {
            initialView: 'dayGridMonth',
            headerToolbar: { left:'prev', center:'title', right:'today next' },
            height: 'auto',
            firstDay: 1,
            events: @json($myAssignments ?? []),
            eventClick: function(info) { openModal(info.event.extendedProps); }
        });
        myCalendar.render();
    }

});
</script>
@endsection