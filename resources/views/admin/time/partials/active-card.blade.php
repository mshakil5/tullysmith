<div class="card border-0 text-white mb-4" style="background:#16a34a;border-radius:16px;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-1">
            <span class="rounded-circle bg-white me-2" style="width:10px;height:10px;display:inline-block;opacity:0.9;"></span>
            <small style="opacity:0.85;">Currently Working</small>
        </div>
        <h5 class="fw-bold mb-1">{{ $log->job->job_title }}</h5>
        <p class="mb-0" style="opacity:0.85;font-size:0.85rem;">Clocked in at {{ $log->clock_in_time }}</p>
        @if($log->location_note && $log->location_note !== 'location_check_failed')
            <small class="d-block mt-1" style="opacity:0.75;">
                <i class="ri-map-pin-line"></i>
                {{ $log->location_note === 'location_verified' ? 'Location verified ✓' : $log->location_note }}
            </small>
        @endif
        <button class="btn btn-white w-100 mt-3 fw-semibold" id="clockOutBtn" style="background:#fff;color:#16a34a;">
            <i class="ri-stop-fill"></i> Clock Out
        </button>
    </div>
</div>