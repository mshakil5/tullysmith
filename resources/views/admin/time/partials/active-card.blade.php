<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <span class="badge badge-soft-success mb-2"><i class="ri-record-circle-line me-1"></i> Currently Working</span>
                <h5 class="fw-semibold mb-1">{{ $log->job->job_title }}</h5>
                <p class="text-muted mb-0 fs-13">Clocked in at <strong>{{ $log->clock_in_time }}</strong></p>
                @if($log->location_note && $log->location_note !== 'location_check_failed')
                    <span class="badge badge-soft-{{ $log->location_note === 'location_verified' ? 'success' : 'warning' }} mt-2 fs-11">
                        <i class="ri-map-pin-line me-1"></i>
                        {{ $log->location_note === 'location_verified' ? 'Location verified' : $log->location_note }}
                    </span>
                @endif
            </div>
            <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-soft-success rounded fs-3">
                    <i class="bx bx-time-five text-success"></i>
                </span>
            </div>
        </div>
        @role('Worker')
        <hr>
        <button class="btn btn-danger w-100" id="clockOutBtn">
            <i class="ri-stop-fill me-1"></i> Clock Out
        </button>
        @endrole
    </div>
</div>