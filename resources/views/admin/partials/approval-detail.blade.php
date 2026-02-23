@php
    if ($type === 'note') {
        $title     = $item->note;
        $jobTitle  = $item->job->job_title ?? '';
        $submitter = $item->user->name ?? '';
        $status    = $item->status;
    } elseif ($type === 'document') {
        $title     = $item->title ?? $item->type;
        $jobTitle  = $item->job->job_title ?? '';
        $submitter = $item->user->name ?? '';
        $status    = $item->status;
    } elseif ($type === 'timelog') {
        $title     = $item->job->job_title ?? '—';
        $jobTitle  = $item->assignment ? $item->assignment->formatted_date : $item->clock_in_at->format('d F Y');
        $submitter = $item->worker->name ?? '';
        $status    = $item->status;
    } else {
        $title     = $item->checklist->title ?? '';
        $jobTitle  = $item->serviceJob->job_title ?? '';
        $submitter = $item->assignedBy->name ?? '';
        $status    = $item->status;
    }
@endphp

<div class="p-1">
    <div class="rounded p-3 mb-3" style="background:#f0f4f8;">
        <h5 class="mb-1">{{ $title }}</h5>
        <div class="text-muted" style="font-size:14px;">{{ $jobTitle }}</div>
        <small class="text-muted">Submitted by {{ $submitter }}</small>
    </div>

    @if ($type === 'note')
        <div class="border rounded p-3 mb-2">
            <p class="mb-0">{{ $item->note }}</p>
        </div>

    @elseif ($type === 'document')
        <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-light text-dark border">{{ ucfirst($item->type) }}</span>
                    @if($item->amount)
                        <span class="badge bg-light text-dark border ms-1">£ {{ $item->amount }}</span>
                    @endif
                    <div class="mt-1 text-muted" style="font-size:13px;">{{ $item->created_at->format('M d, Y H:i') }}</div>
                </div>
                <a href="{{ asset($item->file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="ri-download-2-line"></i> View File
                </a>
            </div>
        </div>

    @elseif ($type === 'timelog')

        {{-- Times row --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">Clock In</p>
                    <p class="fw-bold mb-0">{{ $item->clock_in_at->format('h:i A') }}</p>
                    <small class="text-muted">{{ $item->clock_in_at->format('M d, Y') }}</small>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">Clock Out</p>
                    @if($item->clock_out_at)
                        <p class="fw-bold mb-0">{{ $item->clock_out_at->format('h:i A') }}</p>
                        <small class="text-muted">{{ $item->clock_out_at->format('M d, Y') }}</small>
                    @else
                        <p class="fw-bold mb-0 text-success">Active</p>
                        <small class="text-muted">Still running</small>
                    @endif
                </div>
            </div>
        </div>

        {{-- Total hours --}}
        @if($item->total_hours)
        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
            <span class="text-muted"><i class="ri-time-line me-1"></i> Total Hours</span>
            <strong class="text-primary fs-5">{{ number_format($item->total_hours, 2) }}h</strong>
        </div>
        @endif

        {{-- Photos --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <p class="text-muted mb-1" style="font-size:0.72rem;text-transform:uppercase;">Clock In Photo</p>
                @if($item->clock_in_photo)
                    <img src="{{ $item->clock_in_photo }}" class="w-100 rounded-3"
                         style="max-height:160px;object-fit:cover;cursor:pointer;"
                         onclick="document.getElementById('tlFullImg').src=this.src;new bootstrap.Modal(document.getElementById('tlImgModal')).show();">
                @else
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted" style="height:100px;">
                        <i class="ri-image-line fs-4"></i>
                    </div>
                @endif
            </div>
            <div class="col-6">
                <p class="text-muted mb-1" style="font-size:0.72rem;text-transform:uppercase;">Clock Out Photo</p>
                @if($item->clock_out_photo)
                    <img src="{{ $item->clock_out_photo }}" class="w-100 rounded-3"
                         style="max-height:160px;object-fit:cover;cursor:pointer;"
                         onclick="document.getElementById('tlFullImg').src=this.src;new bootstrap.Modal(document.getElementById('tlImgModal')).show();">
                @else
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted" style="height:100px;">
                        <i class="ri-image-line fs-4"></i>
                    </div>
                @endif
            </div>
        </div>

        {{-- Location --}}
        <div class="border rounded p-3 mb-3">
            <p class="text-muted mb-2" style="font-size:0.72rem;text-transform:uppercase;">Location</p>
            @if($item->clock_in_lat && $item->clock_in_lng)
                <div class="mb-1">
                    <span class="badge {{ $item->location_note === 'location_verified' ? 'bg-success' : ($item->location_note === 'location_check_failed' || !$item->location_note ? 'bg-secondary' : 'bg-warning text-dark') }}">
                        <i class="ri-map-pin-line"></i>
                        @if($item->location_note === 'location_verified') Verified on-site
                        @elseif($item->location_note === 'location_check_failed') Check failed
                        @elseif($item->location_note) {{ $item->location_note }}
                        @else Not checked @endif
                    </span>
                </div>
                <small class="text-muted d-block">
                    {{ $item->clock_in_lat }}, {{ $item->clock_in_lng }}
                </small>
                <a href="https://maps.google.com/?q={{ $item->clock_in_lat }},{{ $item->clock_in_lng }}"
                   target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="ri-map-pin-line"></i> View on Google Maps
                </a>
            @else
                <span class="badge bg-secondary"><i class="ri-map-pin-line"></i> No GPS data provided</span>
            @endif
        </div>

        {{-- Fullscreen image modal (scoped to avoid conflict with parent modal) --}}
        <div class="modal fade" id="tlImgModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark border-0">
                    <div class="modal-header border-0 py-2">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-2 text-center">
                        <img id="tlFullImg" src="" class="img-fluid rounded-2" />
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- Checklist --}}
        @if($item->checklist->items && $item->checklist->items->count() > 0)
            @foreach($item->checklist->items as $checkItem)
                <div class="border rounded p-3 mb-2">
                    <div class="text-muted" style="font-size:12px;">{{ ucfirst(str_replace('_', ' ', $checkItem->type)) }}</div>
                    <strong>{{ $checkItem->question }}</strong>
                    @if($checkItem->answer ?? false)
                        <div class="mt-1" style="font-size:14px;">{{ $checkItem->answer }}</div>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-muted">No checklist items</p>
        @endif
    @endif

    {{-- Status / Actions --}}
    @if($status === 'pending')
        <hr>
        <div class="mb-3">
            <label class="form-label text-muted">Rejection Reason (optional)</label>
            <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Enter reason if rejecting..."></textarea>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger flex-fill actionBtn" data-action="rejected">
                <i class="ri-close-circle-line me-1"></i> Reject
            </button>
            <button class="btn btn-success flex-fill actionBtn" data-action="approved">
                <i class="ri-checkbox-circle-line me-1"></i> Approve
            </button>
        </div>
    @else
        <div class="alert alert-{{ $status === 'approved' ? 'success' : 'danger' }} mt-3 mb-0">
            This item has been <strong>{{ ucfirst($status) }}</strong>.
            @if($item->rejection_reason ?? false)
                <div class="mt-1">Reason: {{ $item->rejection_reason }}</div>
            @endif
        </div>
    @endif
</div>