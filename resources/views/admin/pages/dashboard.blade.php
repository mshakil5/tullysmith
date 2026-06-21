@extends('admin.pages.master')
@section('title', 'Dashboard')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css" rel="stylesheet">
<style>
    .fc-event { cursor: pointer; }
    .fc-event:hover { opacity: 0.9; }
    .add-btn-cell { display: flex; justify-content: center; margin: 4px 4px 2px 4px; }
    .add-btn-cell button { border: 1.5px dashed #cbd5e1; color: #94a3b8; border-radius: 8px; width: 90%; font-size: 1rem; padding: 2px 0; background: transparent; transition: border-color 0.15s, color 0.15s; }
    .add-btn-cell button:hover { border-color: #405189; color: #405189; background: #f0f3fa; }
    .fc-day-past .add-btn-cell button { display: none; }
    #assignFormContainer { display: none; }

    /* Fix select2 width to stay inside col-md-4 */
    #assignForm .select2-container { width: 100% !important; }
    #assignForm .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        display: flex;
        align-items: center;
    }
    #assignForm .select2-container .select2-selection__rendered {
        line-height: normal;
        padding-left: 10px;
        color: #212529;
        font-size: 0.875rem;
    }
    #assignForm .select2-container .select2-selection__arrow { height: 36px; }

    /* Dropdown option styles */
    .opt-wrap { position: relative; padding-right: 85px; min-height: 36px; }
    .opt-line1 { font-weight: 600; font-size: 0.85rem; color: #212529; }
    .opt-line2 { font-size: 0.75rem; margin-top: 1px; }
    .opt-badge { position: absolute; top: 0; right: 0; padding: 2px 8px; border-radius: 5px; font-size: 0.7rem; font-weight: 600; white-space: nowrap; }
    .badge-busy     { background: #fee2e2; color: #dc3545; }
    .badge-avail    { background: #d1fae5; color: #198754; }
    .badge-assigned { background: #fef3c7; color: #d97706; }
    .badge-free     { background: #d1fae5; color: #198754; }
    .text-busy      { color: #dc3545; }
    .text-avail     { color: #198754; }
    .text-assigned  { color: #d97706; }
</style>
@endpush

@section('content')

@if(isset($announcements) && $announcements->count())
<div class="container-fluid mb-2">
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-light border position-relative" type="button" data-bs-toggle="collapse" data-bs-target="#announcementsPanel">
            <i class="bx bx-bell fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;">{{ $announcements->count() }}</span>
        </button>
    </div>
    <div class="collapse show" id="announcementsPanel">
        @foreach($announcements as $ann)
        @php $color = match($ann->priority) { 'high' => 'danger', 'medium' => 'warning', 'low' => 'info', default => 'secondary' }; @endphp
        <div class="alert alert-{{ $color }} d-flex align-items-start gap-2 py-2 mb-2">
            <i class="bx bx-bell fs-5 mt-1"></i>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong>{{ $ann->title }}</strong>
                    @if($ann->job)<span class="badge bg-light text-dark border">{{ $ann->job->job_id }} — {{ $ann->job->job_title }}</span>@endif
                </div>
                <div class="small mt-1 text-break">{!! nl2br(e($ann->content)) !!}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@hasanyrole('Super Admin|Line Manager')

<div class="container-fluid mb-3">
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Workers</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $totalWorker }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-primary rounded fs-3"><i class="bx bx-user text-primary"></i></span></div>
                    </div>
                    <a href="{{ route('employee.index') }}" class="text-primary small">View all workers →</a>
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
                    <a href="{{ route('serviceJob.index') }}" class="text-success small">View active jobs →</a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate shadow-sm">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Draft Jobs</p>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $draftJobs }}</h4>
                        <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-soft-warning rounded fs-3"><i class="bx bx-time-five text-warning"></i></span></div>
                    </div>
                    <a href="{{ route('approvals.index') }}" class="text-warning small">View approvals →</a>
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
                    <a href="{{ route('time.timesheet') }}" class="text-info small">View timesheets →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mb-4">
    <div id="assignFormContainer" class="row justify-content-center">
        <div class="col-xl-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="formCardTitle">New Assignment</h4>
                    <a href="#" id="viewJobBtn" class="btn btn-sm btn-info mt-2" target="_blank" style="display:none;">View Job Details</a>
                    <button type="button" class="btn btn-light btn-sm" id="assignFormCloseBtn">Cancel</button>
                </div>
                <div class="card-body">
                    <form id="assignForm">
                        @csrf
                        <input type="hidden" id="assignment_id" name="assignment_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Job <span class="text-danger">*</span></label>
                                <select name="service_job_id" id="service_job_id" style="width:100%;">
                                    <option value="">Select job</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}" data-label="{{ $job->job_id }} — {{ $job->job_title }}">
                                            {{ $job->job_id }} — {{ $job->job_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Worker <span class="text-danger">*</span></label>
                                <select name="worker_id" id="worker_id" style="width:100%;">
                                    <option value="">Select worker</option>
                                    @foreach($workers as $worker)
                                        <option value="{{ $worker->id }}" data-name="{{ $worker->name }}">
                                            {{ $worker->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assign Date <span class="text-danger">*</span></label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="note" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-danger btn-sm" id="assignDeleteBtn" style="display:none!important;">Delete Assignment</button>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-primary" id="assignSaveBtn">Save Assignment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Job Assignments</h5></div>
        <div class="card-body"><div id="calendar"></div></div>
    </div>
</div>

@endhasanyrole

@role('Worker')
<div class="container-fluid">
    <div class="card mb-4">
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
                                @if($tj['start_time'] || $tj['end_time'])
                                    <p class="mb-0 text-muted" style="font-size:0.78rem;">
                                        <i class="ri-time-line me-1"></i>
                                        {{ $tj['start_time'] ? \Carbon\Carbon::parse($tj['start_time'])->format('h:i A') : '--' }}
                                        —
                                        {{ $tj['end_time'] ? \Carbon\Carbon::parse($tj['end_time'])->format('h:i A') : '--' }}
                                    </p>
                                @endif
                                @if($tj['address'])<p class="mb-0 text-muted"><i class="ri-map-pin-line me-1"></i>{{ $tj['address'] }}</p>@endif
                            </div>
                            <div class="text-end ms-2">
                                @if($tj['assigned_date'] === $todayStr)
                                    <a href="{{ route('time.index') }}" class="btn btn-sm btn-success d-block">Clock In</a>
                                @endif
                            </div>
                        </div>
                    </a>
                    @endforeach
                @endif
            </div>
            <div class="card mt-3">
                <div class="card-header"><h5 class="mb-0">Weekly Assignments</h5></div>
                <div class="card-body"><div id="workerCalendar"></div></div>
            </div>
        </div>
    </div>
</div>
@endrole

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
                    <div class="col-6"><p class="text-muted mb-1 small">Worker</p><p class="fw-semibold mb-0" id="myModalWorker"></p></div>
                    <div class="col-6"><p class="text-muted mb-1 small">Client</p><p class="fw-semibold mb-0" id="myModalClient"></p></div>
                    <div class="col-6"><p class="text-muted mb-1 small">Date</p><p class="fw-semibold mb-0" id="myModalDate"></p></div>
                    <div class="col-6"><p class="text-muted mb-1 small">Status</p><p class="mb-0" id="myModalStatus"></p></div>
                    <div class="col-12"><p class="text-muted mb-1 small">Address</p><p class="fw-semibold mb-0" id="myModalAddress"></p></div>
                    <div class="col-6"><p class="text-muted mb-1 small">Priority</p><p class="mb-0" id="myModalPriority"></p></div>
                    <div class="col-12" id="myModalNoteRow" style="display:none;"><p class="text-muted mb-1 small">Note</p><p class="fw-semibold mb-0" id="myModalNote"></p></div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('time.index') }}" class="btn btn-success btn-sm">Clock In</a>
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

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var allAssignments  = @json($allAssignments ?? []);
    var isEdit          = false;
    var currentId       = null;
    var today           = new Date().toISOString().split('T')[0];

    function isPast(dateStr) { return dateStr < today; }

    function formatDateLabel(dateInput) {
        let d = typeof dateInput === 'string' ? new Date(dateInput + 'T00:00:00') : dateInput;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    function formatDate(d) {
        return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    function statusBadge(val, map) {
        if (!val) return '-';
        let color = map[val] ?? 'secondary';
        let tc = color === 'warning' ? 'text-dark' : '';
        return `<span class="badge bg-${color} ${tc}">${val.charAt(0).toUpperCase() + val.slice(1)}</span>`;
    }

    function workerTemplate(option) {
        if (!option.id) return option.text;
        var workerName      = $(option.element).data('name') || option.text;
        var selectedDate    = $('#assigned_date').val();
        var currentAssignId = $('#assignment_id').val();
        var busy = allAssignments.find(function (a) {
            return String(a.worker_id) === String(option.id)
                && a.assigned_date === selectedDate
                && String(a.id) !== String(currentAssignId);
        });
        var badge = busy ? '<span class="opt-badge badge-busy">Busy</span>' : '<span class="opt-badge badge-avail">Available</span>';
        var line2 = busy
            ? '<div class="opt-line2 text-busy">' + busy.job_id + ' — ' + busy.job_title + '</div>'
            : '<div class="opt-line2 text-avail">Free on this date</div>';
        return $('<div class="opt-wrap">' + badge + '<div class="opt-line1">' + workerName + '</div>' + line2 + '</div>');
    }

    function jobTemplate(option) {
        if (!option.id) return option.text;
        var label           = $(option.element).data('label') || option.text;
        var currentAssignId = $('#assignment_id').val();
        var assigned = allAssignments.filter(function (a) {
            return String(a.service_job_id) === String(option.id)
                && String(a.id) !== String(currentAssignId);
        });
        var badge = assigned.length > 0 ? '<span class="opt-badge badge-assigned">Assigned</span>' : '<span class="opt-badge badge-free">Not Assigned</span>';
        var names = assigned.map(function (a) { return a.worker_name; }).join(', ');
        var line2 = assigned.length > 0
            ? '<div class="opt-line2 text-assigned">' + names + '</div>'
            : '<div class="opt-line2 text-avail">No worker assigned yet</div>';
        return $('<div class="opt-wrap">' + badge + '<div class="opt-line1">' + label + '</div>' + line2 + '</div>');
    }

    // KEY FIX: dropdownParent: $('#assignForm') keeps dropdown inside the form
    // so it inherits the correct col-md-4 width instead of going full page width
    $('#service_job_id').select2({
        width: '100%',
        dropdownParent: $('#assignForm'),
        placeholder: 'Select job',
        templateResult: jobTemplate,
        templateSelection: function (o) { return $(o.element).data('label') || o.text; }
    });

    $('#worker_id').select2({
        width: '100%',
        dropdownParent: $('#assignForm'),
        placeholder: 'Select worker',
        templateResult: workerTemplate,
        templateSelection: function (o) { return $(o.element).data('name') || o.text; }
    });

    $('#assigned_date').on('change', function () {
        var job    = $('#service_job_id').val();
        var worker = $('#worker_id').val();
        $('#service_job_id').val(null).trigger('change');
        $('#worker_id').val(null).trigger('change');
        if (job)    $('#service_job_id').val(job).trigger('change');
        if (worker) $('#worker_id').val(worker).trigger('change');
    });

    if (document.getElementById('calendar')) {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridWeek',
            headerToolbar: { left: 'prev', center: 'title', right: 'today next' },
            firstDay: 1, height: 'auto',
            events: { url: "{{ route('assignment.data') }}", method: 'GET' },
            dayCellDidMount: function (info) {
                if (isPast(info.dateStr)) return;
                var frame = info.el.querySelector('.fc-daygrid-day-frame');
                var wrapper = document.createElement('div'); wrapper.className = 'add-btn-cell';
                var btn = document.createElement('button'); btn.textContent = '+';
                btn.addEventListener('click', function () { openFormNew(info.date); });
                wrapper.appendChild(btn); frame.prepend(wrapper);
                var msg = document.createElement('div'); msg.className = 'no-assignment-msg';
                msg.style.cssText = 'font-size:11px;color:#94a3b8;text-align:center;margin-top:25px;';
                msg.innerText = 'No Assignment'; frame.appendChild(msg);
            },
            eventClick: function (info) { openFormEdit(info.event.id, info.event.extendedProps); },
            eventDidMount: function (info) {
                var p = info.event.extendedProps;
                info.el.setAttribute('title', p.worker_name + ' | ' + p.job_title);
                var dayEl = info.el.closest('.fc-daygrid-day-frame');
                if (dayEl) { var m = dayEl.querySelector('.no-assignment-msg'); if (m) m.style.display = 'none'; }
            }
        });
        calendar.render();
    }

    if (document.getElementById('workerCalendar')) {
        var workerCalendar = new FullCalendar.Calendar(document.getElementById('workerCalendar'), {
            initialView: 'dayGridWeek',
            headerToolbar: { left: 'prev', center: 'title', right: 'today next' },
            firstDay: 1, height: 'auto',
            events: @json($myAssignments ?? []),
            eventClick: function (info) { openModal(info.event.extendedProps); }
        });
        workerCalendar.render();
    }

    function openFormNew(date) {
        isEdit = false; currentId = null;
        $('#assignForm')[0].reset();
        $('#assignment_id').val('');
        var offset = date.getTimezoneOffset();
        var local  = new Date(date.getTime() - offset * 60 * 1000);
        $('#assigned_date').val(local.toISOString().split('T')[0]);
        $('#service_job_id').val(null).trigger('change');
        $('#worker_id').val(null).trigger('change');
        $('#formCardTitle').text('New Assignment — ' + formatDateLabel(date));
        $('#assignSaveBtn').text('Save Assignment');
        $('#assignDeleteBtn').css('display', 'none');
        $('#viewJobBtn').hide();
        $('#assignFormContainer').show(200);
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function openFormEdit(id, p) {
        isEdit = true; currentId = id;
        $('#assignment_id').val(id);
        $('#assigned_date').val(p.assigned_date);
        console.log(p);
        $('#start_time').val(p.start_time ?? '');
        $('#end_time').val(p.end_time ?? '');
        $('#service_job_id').val(p.service_job_id).trigger('change');
        $('#worker_id').val(p.worker_id).trigger('change');
        $('#note').val(p.note ?? '');
        $('#formCardTitle').text('Edit Assignment — ' + formatDateLabel(p.assigned_date));
        $('#assignSaveBtn').text('Update Assignment');
        $('#assignDeleteBtn').css('display', 'inline-block');
        $('#viewJobBtn').attr('href', '/admin/service-job/' + p.service_job_id).show();
        $('#assignFormContainer').show(200);
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function openModal(p) {
        $('#myModalJobTitle').text(p.job_title ?? '');
        $('#myModalJobId').text(p.job_id ?? '');
        $('#myModalWorker').text(p.worker_name ?? '-');
        $('#myModalClient').text(p.client_name ?? '-');
        $('#myModalDate').text(p.assigned_date ? formatDate(p.assigned_date) : '-');
        $('#myModalAddress').text(p.address || '-');
        $('#myModalStatus').html(statusBadge(p.status, { active: 'success', pending: 'warning', draft: 'secondary', completed: 'primary' }));
        $('#myModalPriority').html(statusBadge(p.priority, { low: 'success', medium: 'warning', high: 'danger', urgent: 'danger' }));
        p.note ? ($('#myModalNote').text(p.note), $('#myModalNoteRow').show()) : $('#myModalNoteRow').hide();
        $('#myModalViewBtn').attr('href', '/admin/service-job/' + p.service_job_id);
        $('#myAssignmentModal').modal('show');
    }

    $('#assignFormCloseBtn').click(function () {
        $('#assignFormContainer').hide(200);
        $('#assignForm')[0].reset();
        isEdit = false; currentId = null;
    });

    $('#assignSaveBtn').click(function () {
        var $btn = $(this);
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Sending notification...');

        var fd  = new FormData(document.getElementById('assignForm'));
        var url = isEdit
            ? "{{ url('/admin/dashboard/assignment') }}/" + currentId + "/update"
            : "{{ route('assignment.store') }}";
        $.ajax({
            url: url, method: 'POST', data: fd, contentType: false, processData: false,
            success: function (res) {
                showSuccess(res.message);
                $('#assignFormContainer').hide(200);
                $('#assignForm')[0].reset();
                isEdit = false; currentId = null;
                calendar.refetchEvents();

                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showError(Object.values(xhr.responseJSON.errors)[0][0]);
                } else showError(xhr.responseJSON?.message ?? 'Error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    $('#assignDeleteBtn').click(function () {
        if (!currentId) return;
        showConfirm('Delete this assignment?').then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/admin/dashboard/assignment') }}/" + currentId,
                    method: 'DELETE',
                    success: function (res) {
                        showSuccess(res.message);
                        $('#assignFormContainer').hide(200);
                        $('#assignForm')[0].reset();
                        isEdit = false; currentId = null;
                        calendar.refetchEvents();

                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function (xhr) { showError(xhr.responseJSON?.message ?? 'Error'); }
                });
            }
        });
    });

});
</script>
@endsection