<div class="card mb-2 entry-item" data-log-id="{{ $log->id }}">
    <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-shrink-0">
                @if($log->clock_in_photo)
                    <img src="{{ $log->clock_in_photo }}"
                         class="rounded photo-thumb avatar-sm"
                         style="width:48px;height:48px;object-fit:cover;cursor:pointer;"
                         data-bs-toggle="modal" data-bs-target="#photoModal"
                         data-src="{{ $log->clock_in_photo }}" data-label="Clock In Photo">
                @else
                    <div class="avatar-sm">
                        <span class="avatar-title bg-soft-primary rounded">
                            <i class="bx bx-briefcase-alt text-primary fs-4"></i>
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex-grow-1 overflow-hidden">
                <h6 class="mb-0 text-truncate fw-semibold">{{ $log->job->job_title ?? '—' }}</h6>
                <p class="text-muted mb-0 fs-12">{{ $log->clock_in_at->format('d M Y') }}</p>
                @if($log->location_note && $log->location_note !== 'location_check_failed')
                    <span class="badge badge-soft-{{ $log->location_note === 'location_verified' ? 'success' : 'warning' }} fs-11">
                        <i class="ri-map-pin-line"></i>
                        {{ $log->location_note === 'location_verified' ? 'Verified' : $log->location_note }}
                    </span>
                @endif
            </div>

            <div class="text-end flex-shrink-0">
                @if($log->clock_out_at)
                    <h6 class="fw-bold mb-0 text-primary">{{ $log->total_hours_formatted }}</h6>
                    <p class="text-muted mb-1 fs-12">{{ $log->clock_in_time }} – {{ $log->clock_out_time }}</p>
                    @if($log->clock_out_photo)
                        <img src="{{ $log->clock_out_photo }}"
                             class="rounded photo-thumb"
                             style="width:32px;height:32px;object-fit:cover;cursor:pointer;"
                             data-bs-toggle="modal" data-bs-target="#photoModal"
                             data-src="{{ $log->clock_out_photo }}" data-label="Clock Out Photo">
                    @endif
                @else
                    <span class="badge badge-soft-success">Active</span>
                    <p class="text-muted mb-0 mt-1 fs-12">{{ $log->clock_in_time }}</p>
                @endif
            </div>
        </div>
    </div>
</div>