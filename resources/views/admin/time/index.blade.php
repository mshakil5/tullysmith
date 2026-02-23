@extends('admin.pages.master')
@section('title', 'Time Tracking')

@section('content')
<div class="container-fluid" style="max-width:680px;">

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

    <p class="text-uppercase fw-semibold mb-2 text-muted" style="font-size:0.72rem;letter-spacing:0.07em;">Recent Entries</p>

    <div id="entriesList">
        @forelse($recentLogs as $log)
            @include('admin.time.partials.entry', ['log' => $log])
        @empty
            <p class="text-muted text-center py-3" id="noEntriesMsg">No entries yet</p>
        @endforelse
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
                <video id="cameraPreview" autoplay playsinline class="w-100 rounded-3" style="display:none;"></video>
                <canvas id="photoCanvas" style="display:none;"></canvas>
                <img id="photoPreview" class="w-100 rounded-3" style="display:none;" />
                <div class="mt-2">
                    <span class="badge bg-warning-subtle text-warning" id="locBadge">
                        <i class="ri-map-pin-time-line"></i> <span id="locBadgeText">Getting location…</span>
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" id="retakeBtn" style="display:none;">
                    <i class="ri-refresh-line"></i> Retake
                </button>
                <button class="btn btn-primary" id="captureBtn">
                    <i class="ri-camera-line"></i> Take Photo
                </button>
                <button class="btn btn-success" id="confirmPhotoBtn" style="display:none;" disabled>
                    <i class="ri-check-line"></i> Confirm &amp; Submit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Photo view modal --}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="photoModalLabel">Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <img id="photoModalImg" src="" class="w-100 rounded-2" />
            </div>
        </div>
    </div>
</div>

{{-- Warning confirm modal --}}
<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-error-warning-line text-warning me-2"></i> Warning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="warningMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning" id="warningConfirmBtn">Yes, Continue</button>
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
    var selectedStartTime    = null; // "HH:mm" 24h from data-start
    var selectedEndTime      = null; // "HH:mm" 24h from data-end
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
        $('.job-select-item').css({ background: 'rgba(255,255,255,0.12)', borderColor: 'rgba(255,255,255,0.25)' });
        $(this).css({ background: 'rgba(255,255,255,0.28)', borderColor: '#fff' });
        selectedAssignmentId = $(this).data('id');
        selectedStartTime    = $(this).data('start') || null;
        selectedEndTime      = $(this).data('end')   || null;
    });

    // ── Get current London time as total minutes ──────────────────────────
    function londonMinutes() {
        var londonStr = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Europe/London', hour: '2-digit', minute: '2-digit', hour12: false
        }).format(new Date());
        var parts = londonStr.split(':');
        return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    function londonTimeFormatted() {
        return new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Europe/London', hour: '2-digit', minute: '2-digit', hour12: false
        }).format(new Date());
    }

    function hhmm24toMins(hhmm) {
        var p = hhmm.split(':');
        return parseInt(p[0]) * 60 + parseInt(p[1]);
    }

    function formatTime12(hhmm) {
        var p = hhmm.split(':'), h = parseInt(p[0]), m = p[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    // ── Clock In ──────────────────────────────────────────────────────────
    $(document).on('click', '#clockInBtn', function () {
        if (!selectedAssignmentId) {
            showError('Please select a job first.');
            return;
        }

        // Time warning — more than 30 min before shift start
        if (selectedStartTime) {
            var nowMins   = londonMinutes();
            var startMins = hhmm24toMins(selectedStartTime);
            if (nowMins < startMins - 30) {
                showWarning(
                    'Your shift starts at ' + formatTime12(selectedStartTime) +
                    ' but it\'s currently ' + londonTimeFormatted() + ' (London time). Do you still want to clock in early?',
                    function () { startClockInFlow(); }
                );
                return;
            }
        }

        startClockInFlow();
    });

    function startClockInFlow() {
        isClockOut = false;
        $('#cameraModalTitle').text('Clock In — Take Photo');
        openCamera();
    }

    // ── Clock Out ─────────────────────────────────────────────────────────
    $(document).on('click', '#clockOutBtn', function () {
        // Time warning — more than 15 min before shift end
        if (selectedEndTime) {
            var nowMins = londonMinutes();
            var endMins = hhmm24toMins(selectedEndTime);
            if (nowMins < endMins - 15) {
                showWarning(
                    'Your shift ends at ' + formatTime12(selectedEndTime) +
                    ' but it\'s only ' + londonTimeFormatted() + ' (London time). Do you want to clock out early?',
                    function () { startClockOutFlow(); }
                );
                return;
            }
        }

        startClockOutFlow();
    });

    function startClockOutFlow() {
        isClockOut = true;
        $('#cameraModalTitle').text('Clock Out — Take Photo');
        openCamera();
    }

    // ── Open camera — always stop existing stream first ───────────────────
    function openCamera() {
        stopStream(); // FIX: kill any existing stream before starting new one

        capturedPhoto = null;
        userLat = userLng = null;

        $('#photoPreview').hide().attr('src', '');
        $('#cameraPreview').hide();
        $('#captureBtn').show();
        $('#confirmPhotoBtn').hide().prop('disabled', true);
        $('#retakeBtn').hide();
        setLocBadge('warning', 'Getting location…');

        $('#cameraModal').modal('show');

        // GPS best effort
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (p) { userLat = p.coords.latitude; userLng = p.coords.longitude; setLocBadge('success', 'Location found ✓'); },
                function ()   { setLocBadge('secondary', 'Location unavailable — photo required'); },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            setLocBadge('secondary', 'GPS not supported');
        }

        startCamera();
    }

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
            .then(function (s) {
                stream = s;
                var video = document.getElementById('cameraPreview');
                video.srcObject = s;
                $('#cameraPreview').show();
            })
            .catch(function () {
                setLocBadge('danger', 'Camera access denied');
            });
    }

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        var video = document.getElementById('cameraPreview');
        if (video) video.srcObject = null;
    }

    // ── Capture ───────────────────────────────────────────────────────────
    $('#captureBtn').on('click', function () {
        var video = document.getElementById('cameraPreview'), canvas = document.getElementById('photoCanvas');
        canvas.width  = video.videoWidth  || 640;
        canvas.height = video.videoHeight || 480;
        canvas.getContext('2d').drawImage(video, 0, 0);
        capturedPhoto = canvas.toDataURL('image/jpeg', 0.82);

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
            url: "{{ route('time.clockIn') }}", method: 'POST', contentType: 'application/json',
            data: JSON.stringify({
                job_assignment_id: selectedAssignmentId,
                photo: capturedPhoto,
                lat: userLat,
                lng: userLng,
                force: forceClockIn,
            }),
            success: function (res) {
                if (res.warning) {
                    // Backend says duplicate — hide camera, show warning, let user decide
                    $('#cameraModal').modal('hide');
                    showWarning(res.message, function () {
                        forceClockIn = true;
                        startClockInFlow(); // re-open camera with force=true next submit
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
            error: function (xhr) {
                showError(xhr.responseJSON?.message ?? 'Error clocking in.');
                resetConfirmBtn();
            }
        });
    }

    function doClockOut() {
        $.ajax({
            url: "{{ route('time.clockOut') }}", method: 'POST', contentType: 'application/json',
            data: JSON.stringify({ photo: capturedPhoto }),
            success: function (res) {
                showSuccess(res.message);
                $('#cameraModal').modal('hide');
                $('#clockCardWrapper').html(res.card_html);
                $('#statsWrapper').html(res.stats_html);
                var $existing = $('.entry-item[data-log-id="' + res.log_id + '"]');
                if ($existing.length) $existing.replaceWith(res.entry_html);
                else prependEntry(res.entry_html, res.log_id);
            },
            error: function (xhr) {
                showError(xhr.responseJSON?.message ?? 'Error clocking out.');
                resetConfirmBtn();
            }
        });
    }

    function prependEntry(html, logId) {
        $('#noEntriesMsg').remove();
        $('.entry-item[data-log-id="' + logId + '"]').remove();
        $('#entriesList').prepend(html);
    }

    // Stop stream when modal hides
    $('#cameraModal').on('hide.bs.modal', function () {
        stopStream();
        resetConfirmBtn();
    });

    function resetConfirmBtn() {
        $('#confirmPhotoBtn').prop('disabled', false).html('<i class="ri-check-line"></i> Confirm & Submit');
    }

    function setLocBadge(type, text) {
        $('#locBadge').attr('class', 'badge bg-' + type + '-subtle text-' + type);
        $('#locBadgeText').text(text);
    }

    function showWarning(message, onConfirm) {
        $('#warningMessage').text(message);
        $('#warningConfirmBtn').off('click').on('click', function () {
            $('#warningModal').modal('hide');
            onConfirm();
        });
        $('#warningModal').modal('show');
    }

});
</script>
@endsection