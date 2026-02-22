@php
    if ($type === 'note') {
        $title    = $item->note;
        $jobTitle = $item->job->job_title ?? '';
        $submitter = $item->user->name ?? '';
        $status   = $item->status;
    } elseif ($type === 'document') {
        $title    = $item->title ?? $item->type;
        $jobTitle = $item->job->job_title ?? '';
        $submitter = $item->user->name ?? '';
        $status   = $item->status;
    } else {
        $title    = $item->checklist->title ?? '';
        $jobTitle = $item->serviceJob->job_title ?? '';
        $submitter = $item->assignedBy->name ?? '';
        $status   = $item->status;
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

    @else
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