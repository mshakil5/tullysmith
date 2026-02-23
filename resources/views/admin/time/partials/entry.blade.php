<div class="card border-0 shadow-sm mb-2 entry-item" data-log-id="{{ $log->id }}">
    <div class="card-body p-3">
        <div class="d-flex align-items-start gap-3">
            {{-- Clock-in thumbnail --}}
            <div class="flex-shrink-0">
                @if($log->clock_in_photo)
                    <img src="{{ $log->clock_in_photo }}" alt="In"
                         class="rounded-2 photo-thumb"
                         style="width:48px;height:48px;object-fit:cover;cursor:pointer;"
                         data-bs-toggle="modal" data-bs-target="#photoModal"
                         data-src="{{ $log->clock_in_photo }}" data-label="Clock In Photo">
                @else
                    <div class="rounded-2 d-flex align-items-center justify-content-center bg-light text-secondary"
                         style="width:48px;height:48px;font-size:1.2rem;">
                        <i class="ri-briefcase-line"></i>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-grow-1 min-w-0">
                <h6 class="mb-0 fw-semibold text-truncate" style="font-size:0.88rem;">{{ $log->job->job_title ?? '—' }}</h6>
                <p class="mb-0 text-muted" style="font-size:0.75rem;">{{ $log->clock_in_at->format('M d, Y') }}</p>
                @if($log->location_note && $log->location_note !== 'location_check_failed')
                    <span class="badge {{ $log->location_note === 'location_verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} mt-1" style="font-size:0.65rem;">
                        <i class="ri-map-pin-line"></i>
                        {{ $log->location_note === 'location_verified' ? 'Verified' : $log->location_note }}
                    </span>
                @endif
            </div>

            {{-- Time + clock-out photo --}}
            <div class="text-end flex-shrink-0">
                @if($log->clock_out_at)
                    <h6 class="fw-bold mb-0" style="font-size:0.88rem;">{{ $log->total_hours_formatted }}</h6>
                    <p class="text-muted mb-1" style="font-size:0.72rem;">{{ $log->clock_in_time }} – {{ $log->clock_out_time }}</p>
                    @if($log->clock_out_photo)
                        <img src="{{ $log->clock_out_photo }}" alt="Out"
                             class="rounded-2 photo-thumb"
                             style="width:32px;height:32px;object-fit:cover;cursor:pointer;"
                             data-bs-toggle="modal" data-bs-target="#photoModal"
                             data-src="{{ $log->clock_out_photo }}" data-label="Clock Out Photo">
                    @endif
                @else
                    <span class="badge bg-success">Active</span>
                    <p class="text-muted mb-0 mt-1" style="font-size:0.72rem;">{{ $log->clock_in_time }}</p>
                @endif
            </div>
        </div>
    </div>
</div>