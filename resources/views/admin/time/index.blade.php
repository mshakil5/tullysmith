@extends('admin.pages.master')
@section('title', 'Time Tracking')

@section('content')
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

@endsection

@section('script')
<script>
$(function () {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    var selectedAssignmentId = null;
    var selectedStartTime    = null;
    var selectedEndTime      = null;
    var capturedPhoto        = null;
    var userLat = null, userLng = null;
    var stream  = null;
    var isClockOut   = false;
    var forceClockIn = false;

    // ── Photo view modal ──────────────────────────────────────────────────
    $(document).on('click', '.photo-thumb', function () {
        $('#photoModalImg').attr('src', $(this).data('src'));
        $('#photoModalLabel').text($(this).data('label'));
    });

    // ── Job selection ─────────────────────────────────────────────────────
    $(document).on('click', '.job-select-item', function () {
        $('.job-select-item').removeClass('border-primary').css('background', '');
        $(this).addClass('border-primary').css('background', 'var(--vz-light)');
        selectedAssignmentId = $(this).data('id');
        selectedStartTime    = $(this).data('start') || null;
        selectedEndTime      = $(this).data('end')   || null;
    });

    // ── London time helpers ───────────────────────────────────────────────
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

    // ── Clock In ──────────────────────────────────────────────────────────
    $(document).on('click', '#clockInBtn', function () {
        if (!selectedAssignmentId) { showError('Please select a job first.'); return; }

        if (selectedStartTime) {
            var nowMins = londonMinutes(), startMins = hhmm24toMins(selectedStartTime);
            if (nowMins < startMins - 30) {
                showConfirm('Your shift starts at ' + formatTime12(selectedStartTime) + ' but it\'s currently ' + londonTimeFormatted() + ' Clock in early?')
                    .then(function(r) { if (r.isConfirmed) startClockInFlow(); });
                return;
            }
        }
        startClockInFlow();
    });

    // ── Clock Out ─────────────────────────────────────────────────────────
    $(document).on('click', '#clockOutBtn', function () {
        if (selectedEndTime) {
            var nowMins = londonMinutes(), endMins = hhmm24toMins(selectedEndTime);
            if (nowMins < endMins - 15) {
                showConfirm('Your shift ends at ' + formatTime12(selectedEndTime) + ' but it\'s only ' + londonTimeFormatted() + 'Clock out early?')
                    .then(function(r) { if (r.isConfirmed) startClockOutFlow(); });
                return;
            }
        }
        startClockOutFlow();
    });

    function startClockInFlow()  { isClockOut = false; $('#cameraModalTitle').text('Clock In — Take Photo');  openCamera(); }
    function startClockOutFlow() { isClockOut = true;  $('#cameraModalTitle').text('Clock Out — Take Photo'); openCamera(); }

    // ── Camera ────────────────────────────────────────────────────────────
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

    // ── Capture ───────────────────────────────────────────────────────────
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

    // ── Retake ────────────────────────────────────────────────────────────
    $('#retakeBtn').on('click', function () {
        capturedPhoto = null;
        $('#photoPreview').hide();
        $('#captureBtn').show();
        $('#retakeBtn').hide();
        $('#confirmPhotoBtn').hide().prop('disabled', true);
        startCamera();
    });

    // ── Confirm ───────────────────────────────────────────────────────────
    $('#confirmPhotoBtn').on('click', function () {
        if (!capturedPhoto) { showError('Please take a photo first.'); return; }
        $(this).prop('disabled', true).html('<i class="ri-loader-4-line"></i> Submitting…');
        isClockOut ? doClockOut() : doClockIn();
    });

    function doClockIn() {
        $.ajax({
            url: "{{ route('time.clockIn') }}", method:'POST', contentType:'application/json',
            data: JSON.stringify({ job_assignment_id:selectedAssignmentId, photo:capturedPhoto, lat:userLat, lng:userLng, force:forceClockIn }),
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
                forceClockIn = false;
            },
            error: function(xhr) { showError(xhr.responseJSON?.message ?? 'Error clocking in.'); resetConfirmBtn(); }
        });
    }

    function doClockOut() {
        $.ajax({
            url: "{{ route('time.clockOut') }}", method:'POST', contentType:'application/json',
            data: JSON.stringify({ photo:capturedPhoto }),
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

});
</script>
@endsection