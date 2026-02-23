@extends('admin.pages.master')
@section('title', 'Job Assignments')

@push('css')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
<style>
    .fc { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.07); }
    .fc-toolbar-title { font-size: 1rem !important; font-weight: 700; }
    .fc-button { background: #405189 !important; border-color: #405189 !important; border-radius: 8px !important; font-size: 0.8rem !important; box-shadow: none !important; }
    .fc-button:hover { background: #333f6b !important; border-color: #333f6b !important; }
    .fc-col-header-cell { background: #f8fafc; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 10px 0 !important; color: #64748b; font-weight: 700; }
    .fc-daygrid-day-number { font-size: 1rem; font-weight: 700; color: #1e293b; padding: 8px 12px !important; }
    .fc-day-today .fc-daygrid-day-number { color: #405189; background: #e8ecf5; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .fc-daygrid-day.fc-day-today { background: #f0f3fa !important; }
    .fc-daygrid-day-frame { min-height: 150px; }
    .fc-daygrid-day-top { justify-content: center; padding-top: 6px; }
    .fc-event { background: #f1f5f9 !important; color: #1e293b !important; border-left: 4px solid #405189 !important; border-radius: 8px !important; padding: 6px 8px !important; font-size: 0.8rem !important; font-weight: 600; }
    .fc-event:hover { background: #405189 !important; color: #ffffff !important; transform: translateY(-1px); }
    .fc-event-title { font-weight: 600; }
    .add-btn-cell { display: flex; justify-content: center; margin: 4px 4px 2px 4px; }
    .add-btn-cell button { border: 1.5px dashed #cbd5e1; color: #94a3b8; border-radius: 8px; width: 90%; font-size: 1rem; padding: 2px 0; background: transparent; transition: border-color 0.15s, color 0.15s; }
    .add-btn-cell button:hover { border-color: #405189; color: #405189; background: #f0f3fa; }
    .fc-day-past .add-btn-cell button { display: none; }
    #assignFormContainer { display: none; }
</style>
@endpush

@section('content')
<div class="container mb-4">
    <div id="assignFormContainer" class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0" id="formCardTitle">New Assignment</h4>
                    <button type="button" class="btn btn-light btn-sm" id="assignFormCloseBtn">Cancel</button>
                </div>
                <div class="card-body">
                    <form id="assignForm">
                        @csrf
                        <input type="hidden" id="assignment_id" name="assignment_id">
                        <input type="hidden" id="assigned_date" name="assigned_date">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Job <span class="text-danger">*</span></label>
                                <select name="service_job_id" id="service_job_id" class="form-control select2">
                                    <option value="">Select job</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}">{{ $job->job_id }} — {{ $job->job_title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Staff Member <span class="text-danger">*</span></label>
                                <select name="worker_id" id="worker_id" class="form-control select2">
                                    <option value="">Select staff</option>
                                    @foreach($workers as $worker)
                                        <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="note" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary" id="assignSaveBtn">Save Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div id="calendar"></div>
</div>
@endsection

@section('script')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
$(function () {

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var isEdit = false;
    var currentId = null;
    var today = new Date().toISOString().split('T')[0];

    function isPast(dateStr) { return dateStr < today; }

    function formatDateLabel(dateInput) {
        let d;
        if (typeof dateInput === 'string') d = new Date(dateInput + 'T00:00:00');
        else if (dateInput instanceof Date) d = dateInput;
        else return 'Invalid Date';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridWeek',
        headerToolbar: { left: 'prev', center: 'title', right: 'today next' },
        firstDay: 1,
        height: 'auto',
        events: { url: "{{ route('jobAssignment.data') }}", method: 'GET' },
        dayCellDidMount: function (info) {
            if (isPast(info.dateStr)) return;
            var frame = info.el.querySelector('.fc-daygrid-day-frame');

            // + button
            var wrapper = document.createElement('div');
            wrapper.className = 'add-btn-cell';
            var btn = document.createElement('button');
            btn.textContent = '+';
            btn.addEventListener('click', function () { openFormNew(info.date); });
            wrapper.appendChild(btn);
            frame.prepend(wrapper);

            // "No Assignment" message
            var emptyMsg = document.createElement('div');
            emptyMsg.className = 'no-assignment-msg';
            emptyMsg.style.fontSize = '11px';
            emptyMsg.style.color = '#94a3b8';
            emptyMsg.style.textAlign = 'center';
            emptyMsg.style.marginTop = '25px';
            emptyMsg.innerText = 'No Assignment';
            frame.appendChild(emptyMsg);
        },
        eventClick: function (info) {
            var p = info.event.extendedProps;
            openFormEdit(info.event.id, p);
        },
        eventDidMount: function(info) {
            var p = info.event.extendedProps;
            var timeStr = (p.start_time ? p.start_time.substring(0,5) : '') + (p.end_time ? ' — ' + p.end_time.substring(0,5) : '');
            info.el.setAttribute('title', p.worker_name + ' | ' + p.job_title + (timeStr ? ' | ' + timeStr : ''));
            var dayEl = info.el.closest('.fc-daygrid-day-frame');
            if(dayEl) { var msg = dayEl.querySelector('.no-assignment-msg'); if(msg) msg.style.display = 'none'; }
        }
    });

    calendar.render();

    function openFormNew(date) {
        isEdit = false;
        currentId = null;
        $('#assignForm')[0].reset();
        $('#assignment_id').val('');

        const offset = date.getTimezoneOffset(); 
        const localDate = new Date(date.getTime() - offset * 60 * 1000);
        const dateStr = localDate.toISOString().split('T')[0];

        $('#assigned_date').val(dateStr);
        $('#service_job_id').val(null).trigger('change');
        $('#worker_id').val(null).trigger('change');
        $('#formCardTitle').text('New Assignment — ' + formatDateLabel(date));
        $('#assignSaveBtn').text('Save Assignment');
        $('#assignFormContainer').show(200);
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function openFormEdit(id, p) {
        isEdit = true;
        currentId = id;
        $('#assignment_id').val(id);
        $('#assigned_date').val(p.assigned_date);
        $('#service_job_id').val(p.service_job_id).trigger('change');
        $('#worker_id').val(p.worker_id).trigger('change');
        $('#start_time').val(p.start_time ? p.start_time.substring(0,5) : '');
        $('#end_time').val(p.end_time ? p.end_time.substring(0,5) : '');
        $('#note').val(p.note ?? '');
        $('#formCardTitle').text('Edit Assignment — ' + formatDateLabel(p.assigned_date));
        $('#assignSaveBtn').text('Update Assignment');
        $('#assignFormContainer').show(200);
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    $('#assignFormCloseBtn').click(function () {
        $('#assignFormContainer').hide(200);
        $('#assignForm')[0].reset();
        isEdit = false;
        currentId = null;
    });

    $('#assignSaveBtn').click(function () {
        var fd = new FormData(document.getElementById('assignForm'));
        var url = isEdit
            ? "{{ url('/admin/job-assignment') }}/" + currentId + "/update"
            : "{{ route('jobAssignment.store') }}";

        $.ajax({
            url: url,
            method: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success: function (res) {
                showSuccess(res.message);
                $('#assignFormContainer').hide(200);
                $('#assignForm')[0].reset();
                isEdit = false;
                currentId = null;
                calendar.refetchEvents();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    let first = Object.values(xhr.responseJSON.errors)[0][0];
                    showError(first);
                } else showError(xhr.responseJSON?.message ?? 'Error');
            }
        });
    });

});
</script>
@endsection