@extends('admin.pages.master')
@section('title', 'Time Tracking')

@section('content')
@role('Worker')
<div class="container-fluid">

    <div class="row">
        <div class="col-xl-5 col-lg-6">

            <div id="clockCardWrapper">
                @if($activeLog)
                    @include('admin.time.partials.active-card', ['log' => $activeLog])
                @else
                    @include('admin.time.partials.start-card', ['todayAssignments' => $todayAssignments])
                @endif
            </div>

            <div id="statsWrapper">
                @include('admin.time.partials.stats', ['todayHours' => $todayHours, 'weekHours' => $weekHours, 'monthHours' => $monthHours])
            </div>

        </div>

        <div class="col-xl-7 col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Entries</h5>
                    <a href="{{ route('time.timesheet') }}" class="btn btn-sm btn-soft-primary">
                        <i class="ri-calendar-line me-1"></i> View Timesheet
                    </a>
                </div>
                <div class="card-body p-3">
                    <div id="entriesList">
                        @forelse($recentLogs as $log)
                            @include('admin.time.partials.entry', ['log' => $log])
                        @empty
                            <p class="text-muted text-center py-3 mb-0" id="noEntriesMsg">No entries yet</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Camera Modal --}}
<div class="modal fade" id="cameraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cameraModalTitle">Clock In — Take Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <video id="cameraPreview" autoplay playsinline class="w-100 rounded" style="display:none;"></video>
                <canvas id="photoCanvas" style="display:none;"></canvas>
                <img id="photoPreview" class="w-100 rounded" style="display:none;" />
                <div class="mt-2">
                    <span class="badge badge-soft-warning" id="locBadge">
                        <i class="ri-map-pin-time-line me-1"></i><span id="locBadgeText">Getting location…</span>
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" id="retakeBtn" style="display:none;">
                    <i class="ri-refresh-line me-1"></i> Retake
                </button>
                <button class="btn btn-primary" id="captureBtn">
                    <i class="ri-camera-line me-1"></i> Take Photo
                </button>
                <button class="btn btn-success" id="confirmPhotoBtn" style="display:none;" disabled>
                    <i class="ri-check-line me-1"></i> Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Photo View Modal --}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="photoModalLabel">Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <img id="photoModalImg" src="" class="w-100 rounded" />
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="checklistModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checklistModalTitle">Complete Checklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="clockChecklistForm" enctype="multipart/form-data">
                    @csrf
                    <div id="clockChecklistContent"></div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary" id="checklistProceedBtn">
                            <i class="ri-check-line me-1"></i> Save & Proceed
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endrole

@unlessrole('Worker')
<div class="row mt-4" id="adminSection">

    {{-- Worker Selector --}}
    <div class="col-12 mb-3">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <label class="fw-semibold mb-0 text-nowrap">
                        <i class="ri-user-settings-line me-1 text-primary"></i> Select Worker to Manage:
                    </label>
                    <select id="adminWorkerSelect" class="form-control select2" style="max-width:300px;">
                        <option value="">— Choose a worker —</option>
                        @foreach($workers as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endforeach
                    </select>
                    <span id="adminLoadingSpinner" class="text-muted d-none">
                        <i class="ri-loader-4-line"></i> Loading…
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Admin Worker Panel (hidden until worker selected) --}}
    <div id="adminWorkerPanel" class="d-none">
        <div class="row">
            <div class="col-xl-5 col-lg-6">
                <div id="adminClockCardWrapper"></div>
                <div id="adminStatsWrapper"></div>
            </div>
            <div class="col-xl-7 col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ri-time-line me-1"></i>
                            Recent Entries — <span id="adminWorkerName"></span>
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <div id="adminEntriesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Manual Clock In Modal --}}
<div class="modal fade" id="manualClockInModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-shield-user-line me-1 text-primary"></i>
                    Clock In As Admin — <span id="manualWorkerName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualClockInForm" enctype="multipart/form-data"
                      action="{{ route('time.manualClockIn') }}" method="POST">
                    @csrf
                    <input type="hidden" name="worker_id" id="manual_worker_id">
                    <input type="hidden" name="job_assignment_id" id="manual_assignment_id">

                    <div class="alert alert-soft-info py-2 px-3 mb-3">
                        <i class="ri-information-line me-1"></i>
                        Job: <strong id="manual_job_title"></strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Clock In <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="clock_in_at" id="manual_clock_in" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Clock Out</label>
                            <input type="datetime-local" name="clock_out_at" id="manual_clock_out" class="form-control">
                            <div class="form-text">Leave blank if still active.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Clock-In Photo <span class="text-muted fw-normal">(optional)</span></label>
                            <img src="" class="d-none d-block mb-2 rounded" style="height:70px;object-fit:cover;">
                            <input type="file" name="clock_in_photo" class="form-control" accept="image/*"
                                onchange="this.previousElementSibling.src=window.URL.createObjectURL(this.files[0]); this.previousElementSibling.classList.remove('d-none')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Clock-Out Photo <span class="text-muted fw-normal">(optional)</span></label>
                            <img src="" class="d-none d-block mb-2 rounded" style="height:70px;object-fit:cover;">
                            <input type="file" name="clock_out_photo" class="form-control" accept="image/*"
                                onchange="this.previousElementSibling.src=window.URL.createObjectURL(this.files[0]); this.previousElementSibling.classList.remove('d-none')">
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="manualClockInSubmitBtn">
                            <i class="ri-shield-check-line me-1"></i> Confirm Clock In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endunlessrole

@endsection

@section('script')
<script>
$(function () {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var selectedAssignmentId  = null;
    var selectedStartTime     = null;
    var selectedEndTime       = null;
    var selectedServiceJobId  = null;
    var capturedPhoto         = null;
    var userLat = null, userLng = null;
    var stream  = null;
    var isClockOut         = false;
    var forceClockIn       = false;
    var forceLocation      = false;
    var pendingClockAction = null;

    $(document).on('click', '.photo-thumb', function () {
        $('#photoModalImg').attr('src', $(this).data('src'));
        $('#photoModalLabel').text($(this).data('label'));
    });

    $(document).on('click', '.job-select-item', function () {
        $('.job-select-item').removeClass('border-primary').css('background', '');
        $(this).addClass('border-primary').css('background', 'var(--vz-light)');
        selectedAssignmentId = $(this).data('id');
        selectedServiceJobId = $(this).data('job-id')  || null;
        selectedStartTime    = $(this).data('start')   || null;
        selectedEndTime      = $(this).data('end')     || null;
    });

    function londonMinutes() {
        var s = new Intl.DateTimeFormat('en-GB', { timeZone:'Europe/London', hour:'2-digit', minute:'2-digit', hour12:false }).format(new Date());
        var p = s.split(':');
        return parseInt(p[0]) * 60 + parseInt(p[1]);
    }

    function londonTimeFormatted() {
        return new Intl.DateTimeFormat('en-GB', { timeZone:'Europe/London', hour:'2-digit', minute:'2-digit', hour12:false }).format(new Date());
    }

    function hhmm24toMins(hhmm) {
        var p = hhmm.split(':');
        return parseInt(p[0]) * 60 + parseInt(p[1]);
    }

    function formatTime12(hhmm) {
        var p = hhmm.split(':'), h = parseInt(p[0]), m = p[1];
        return (h % 12 || 12) + ':' + m + ' ' + (h >= 12 ? 'PM' : 'AM');
    }

    function haversineJS(lat1, lng1, lat2, lng2) {
        var R    = 6371000;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a    = Math.sin(dLat/2) * Math.sin(dLat/2) +
                   Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                   Math.sin(dLng/2) * Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function checkLocation(postcode, type, onConfirm) {
        var onProceed = type === 'clock_in' ? startClockInFlow : startClockOutFlow;
        var action    = type === 'clock_in' ? 'clock in' : 'clock out';

        if (!navigator.geolocation || !postcode) { onProceed(); return; }

        showLoader();
        navigator.geolocation.getCurrentPosition(function(pos) {
            fetch('https://api.postcodes.io/postcodes/' + encodeURIComponent(postcode))
                .then(function(r) { return r.json(); })
                .then(function(geo) {
                    Swal.close();
                    if (geo.status === 200) {
                        var dist = haversineJS(pos.coords.latitude, pos.coords.longitude, geo.result.latitude, geo.result.longitude);
                        if (dist > 100) {
                            showConfirm('You are ' + Math.round(dist) + 'm away from the job site. Do you want to ' + action + ' anyway?')
                                .then(function(r) {
                                    if (r.isConfirmed) {
                                        if (onConfirm) onConfirm();
                                        onProceed();
                                    }
                                });
                            return;
                        }
                    }
                    onProceed();
                })
                .catch(function() { Swal.close(); onProceed(); });
        }, function() { Swal.close(); onProceed(); }, { enableHighAccuracy:true, timeout:10000, maximumAge:0 });
    }

    $(document).on('click', '#clockInBtn', function () {
        if (!selectedAssignmentId) { showError('Please select a job first.'); return; }

        var postcode = $('.job-select-item.border-primary').data('postcode') || null;

        if (selectedStartTime) {
            var nowMins = londonMinutes(), startMins = hhmm24toMins(selectedStartTime);
            if (nowMins < startMins - 30) {
                showConfirm('Your shift starts at ' + formatTime12(selectedStartTime) + ' but it\'s currently ' + londonTimeFormatted() + ' Clock in early?')
                    .then(function(r) { if (r.isConfirmed) checkLocation(postcode, 'clock_in', function() { forceLocation = true; }); });
                return;
            }
        }
        checkLocation(postcode, 'clock_in', function() { forceLocation = true; });
    });

    $(document).on('click', '#clockOutBtn', function () {
        var postcode = $('.job-select-item.border-primary').data('postcode') || $('#clockOutBtn').data('postcode') || null;

        if (selectedEndTime) {
            var nowMins = londonMinutes(), endMins = hhmm24toMins(selectedEndTime);
            if (nowMins < endMins - 15) {
                showConfirm('Your shift ends at ' + formatTime12(selectedEndTime) + ' but it\'s only ' + londonTimeFormatted() + ' Clock out early?')
                    .then(function(r) { if (r.isConfirmed) checkLocation(postcode, 'clock_out', null); });
                return;
            }
        }
        checkLocation(postcode, 'clock_out', null);
    });

    function startClockInFlow() {
        if (!selectedServiceJobId) {
            isClockOut = false;
            $('#cameraModalTitle').text('Clock In — Take Photo');
            openCamera();
            return;
        }
        $.get("{{ route('time.checklistQuestions') }}", { service_job_id: selectedServiceJobId, type: 'clock_in' }, function(res) {
            if (res.has_checklists) {
                pendingClockAction = 'clock_in';
                $('#checklistModalTitle').text('Clock In Checklist');
                $('#clockChecklistContent').html(res.html);
                $('#checklistProceedBtn').text('Save & Clock In');
                new bootstrap.Modal(document.getElementById('checklistModal')).show();
            } else {
                isClockOut = false;
                $('#cameraModalTitle').text('Clock In — Take Photo');
                openCamera();
            }
        }).fail(function() { showError('Failed to load checklists.'); });
    }

    function startClockOutFlow() {
        var jobId = selectedServiceJobId || $('#clockOutBtn').data('job-id') || null;
        if (!jobId) {
            isClockOut = true;
            $('#cameraModalTitle').text('Clock Out — Take Photo');
            openCamera();
            return;
        }
        $.get("{{ route('time.checklistQuestions') }}", { service_job_id: jobId, type: 'clock_out' }, function(res) {
            if (res.has_checklists) {
                pendingClockAction = 'clock_out';
                $('#checklistModalTitle').text('Clock Out Checklist');
                $('#clockChecklistContent').html(res.html);
                $('#checklistProceedBtn').text('Save & Clock Out');
                new bootstrap.Modal(document.getElementById('checklistModal')).show();
            } else {
                isClockOut = true;
                $('#cameraModalTitle').text('Clock Out — Take Photo');
                openCamera();
            }
        }).fail(function() { showError('Failed to load checklists.'); });
    }

    $('#clockChecklistForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#checklistProceedBtn');
        btn.prop('disabled', true).html('<i class="ri-loader-4-line"></i> Saving...');
        var fd = new FormData(this);
        $.ajax({
            url: "{{ route('time.saveClockChecklistAnswers') }}",
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function() {
                bootstrap.Modal.getInstance(document.getElementById('checklistModal')).hide();
                btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Save & Proceed');
                if (pendingClockAction === 'clock_in') {
                    isClockOut = false;
                    $('#cameraModalTitle').text('Clock In — Take Photo');
                    openCamera();
                } else if (pendingClockAction === 'clock_out') {
                    isClockOut = true;
                    $('#cameraModalTitle').text('Clock Out — Take Photo');
                    openCamera();
                }
                pendingClockAction = null;
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Save & Proceed');
                var res = xhr.responseJSON;
                if (res?.missing?.length) {
                    showError('Required questions missing:<br>' + res.missing.map(function(q) { return '• ' + q; }).join('<br>'));
                } else {
                    showError(res?.message || 'Failed to save answers.');
                }
            }
        });
    });

    function openCamera() {
        stopStream();
        capturedPhoto = null;
        userLat = userLng = null;

        $('#photoPreview').hide().attr('src', '');
        $('#cameraPreview').hide();
        $('#captureBtn').show();
        $('#confirmPhotoBtn').hide().prop('disabled', true);
        $('#retakeBtn').hide();
        setLocBadge('warning', 'Getting location…');
        $('#cameraModal').modal('show');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(p) { userLat = p.coords.latitude; userLng = p.coords.longitude; setLocBadge('success', 'Location found ✓'); },
                function()  { setLocBadge('secondary', 'Location unavailable'); },
                { enableHighAccuracy:true, timeout:10000, maximumAge:0 }
            );
        } else {
            setLocBadge('secondary', 'GPS not supported');
        }

        startCamera();
    }

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode:'environment' }, audio:false })
            .then(function(s) {
                stream = s;
                var v = document.getElementById('cameraPreview');
                v.srcObject = s;
                $('#cameraPreview').show();
            })
            .catch(function() { setLocBadge('danger', 'Camera access denied'); });
    }

    function stopStream() {
        if (stream) { stream.getTracks().forEach(function(t) { t.stop(); }); stream = null; }
        var v = document.getElementById('cameraPreview');
        if (v) v.srcObject = null;
    }

    $('#captureBtn').on('click', function () {
        var v = document.getElementById('cameraPreview'), c = document.getElementById('photoCanvas');
        c.width = v.videoWidth || 640; c.height = v.videoHeight || 480;
        c.getContext('2d').drawImage(v, 0, 0);
        capturedPhoto = c.toDataURL('image/jpeg', 0.82);
        $('#photoPreview').attr('src', capturedPhoto).show();
        $('#cameraPreview').hide();
        $('#captureBtn').hide();
        $('#retakeBtn').show();
        $('#confirmPhotoBtn').show().prop('disabled', false);
        stopStream();
    });

    $('#retakeBtn').on('click', function () {
        capturedPhoto = null;
        $('#photoPreview').hide();
        $('#captureBtn').show();
        $('#retakeBtn').hide();
        $('#confirmPhotoBtn').hide().prop('disabled', true);
        startCamera();
    });

    $('#confirmPhotoBtn').on('click', function () {
        if (!capturedPhoto) { showError('Please take a photo first.'); return; }
        $(this).prop('disabled', true).html('<i class="ri-loader-4-line"></i> Submitting…');
        isClockOut ? doClockOut() : doClockIn();
    });

    function doClockIn() {
        $.ajax({
            url: "{{ route('time.clockIn') }}", method:'POST', contentType:'application/json',
            data: JSON.stringify({ job_assignment_id:selectedAssignmentId, photo:capturedPhoto, lat:userLat, lng:userLng, force:forceClockIn, force_location:forceLocation }),
            success: function(res) {
                if (res.warning) {
                    $('#cameraModal').modal('hide');
                    showConfirm(res.message).then(function(r) {
                        if (r.isConfirmed) { forceClockIn = true; startClockInFlow(); }
                    });
                    resetConfirmBtn();
                    return;
                }
                showSuccess(res.message);
                $('#cameraModal').modal('hide');
                $('#clockCardWrapper').html(res.card_html);
                $('#statsWrapper').html(res.stats_html);
                prependEntry(res.entry_html, res.log_id);
                selectedAssignmentId = null;
                selectedServiceJobId = null;
                forceClockIn  = false;
                forceLocation = false;
            },
            error: function(xhr) { showError(xhr.responseJSON?.message ?? 'Error clocking in.'); resetConfirmBtn(); }
        });
    }

    function doClockOut() {
        $.ajax({
            url: "{{ route('time.clockOut') }}", method:'POST', contentType:'application/json',
            data: JSON.stringify({ photo:capturedPhoto, lat:userLat, lng:userLng }),
            success: function(res) {
                showSuccess(res.message);
                $('#cameraModal').modal('hide');
                $('#clockCardWrapper').html(res.card_html);
                $('#statsWrapper').html(res.stats_html);
                var $ex = $('.entry-item[data-log-id="' + res.log_id + '"]');
                if ($ex.length) $ex.replaceWith(res.entry_html);
                else prependEntry(res.entry_html, res.log_id);
            },
            error: function(xhr) { showError(xhr.responseJSON?.message ?? 'Error clocking out.'); resetConfirmBtn(); }
        });
    }

    function prependEntry(html, logId) {
        $('#noEntriesMsg').remove();
        $('.entry-item[data-log-id="' + logId + '"]').remove();
        $('#entriesList').prepend(html);
    }

    $('#cameraModal').on('hide.bs.modal', function () { stopStream(); resetConfirmBtn(); });

    function resetConfirmBtn() {
        $('#confirmPhotoBtn').prop('disabled', false).html('<i class="ri-check-line me-1"></i> Confirm & Submit');
    }

    function setLocBadge(type, text) {
        $('#locBadge').attr('class', 'badge badge-soft-' + type);
        $('#locBadgeText').text(text);
    }

    var adminWorkerId = null;

    $('#adminWorkerSelect').on('change', function () {
        adminWorkerId = $(this).val();
        if (!adminWorkerId) { $('#adminWorkerPanel').addClass('d-none'); return; }

        $('#adminLoadingSpinner').removeClass('d-none');
        $.get("{{ route('time.workerData') }}", { worker_id: adminWorkerId }, function (res) {
            $('#adminWorkerName').text(res.worker_name);
            $('#manualWorkerName').text(res.worker_name);
            $('#adminClockCardWrapper').html(res.active_card_html);
            $('#adminStatsWrapper').html(res.stats_html);
            $('#adminEntriesList').html(res.entries_html || '<p class="text-muted text-center py-3 mb-0">No entries yet</p>');
            $('#adminWorkerPanel').removeClass('d-none');
            $('#adminLoadingSpinner').addClass('d-none');
        }).fail(function () {
            $('#adminLoadingSpinner').addClass('d-none');
            showError('Failed to load worker data.');
        });
    });

    $(document).on('click', '.admin-clockin-btn', function () {
        var assignmentId = $(this).data('assignment-id');
        var jobTitle     = $(this).data('job-title');
        var today        = new Date().toISOString().slice(0, 16);

        $('#manual_worker_id').val(adminWorkerId);
        $('#manual_assignment_id').val(assignmentId);
        $('#manual_job_title').text(jobTitle);
        $('#manual_clock_in').val(today);
        $('#manual_clock_out').val('');
        $('#manualClockInModal').modal('show');
    });

    $('#manualClockInForm').on('submit', function (e) {
        e.preventDefault();
        showConfirm('Clock in this worker as Admin? This will create an official time entry.')
            .then(function (r) {
                if (!r.isConfirmed) return;

                var fd = new FormData(document.getElementById('manualClockInForm'));
                $('#manualClockInSubmitBtn').prop('disabled', true).html('<i class="ri-loader-4-line"></i> Saving…');

                $.ajax({
                    url: "{{ route('time.manualClockIn') }}",
                    method: 'POST',
                    data: fd,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        showSuccess(res.message);
                        $('#manualClockInModal').modal('hide');
                        $.get("{{ route('time.workerData') }}", { worker_id: adminWorkerId }, function (res) {
                            $('#adminClockCardWrapper').html(res.active_card_html);
                            $('#adminStatsWrapper').html(res.stats_html);
                            $('#adminEntriesList').html(res.entries_html || '<p class="text-muted text-center py-3 mb-0">No entries yet</p>');
                        });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON.errors) {
                            var first = Object.values(xhr.responseJSON.errors)[0][0];
                            showError(first);
                        } else {
                            showError(xhr.responseJSON?.message ?? 'Error saving entry.');
                        }
                    },
                    complete: function () {
                        $('#manualClockInSubmitBtn').prop('disabled', false).html('<i class="ri-shield-check-line me-1"></i> Confirm Clock In');
                    }
                });
            });
    });

});
</script>
@endsection