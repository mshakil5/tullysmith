@php
    if ($type === 'timelog') {
        $title     = $item->job->job_title ?? '—';
        $jobTitle  = $item->assignment ? $item->assignment->formatted_date : $item->clock_in_at->format('d F Y');
        $submitter = $item->worker->name ?? '';
        $status    = $item->status;
    } elseif ($type === 'servicejob') {
        $title     = $item->job_title ?? '—';
        $jobTitle  = $item->job_id ?? '';
        $submitter = $item->client->name ?? '';
        $status    = $item->status === 'completed' ? 'pending' : ($item->status === 'confirmed' ? 'approved' : $item->status);
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
        <small class="text-muted">{{ $type === 'servicejob' ? 'Client' : 'Submitted by' }}: {{ $submitter }}</small>
    </div>

    @if ($type === 'timelog')

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1 small text-uppercase">Clock In</p>
                    <p class="fw-bold mb-0">{{ $item->clock_in_at->format('h:i A') }}</p>
                    <small class="text-muted">{{ $item->clock_in_at->format('M d, Y') }}</small>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1 small text-uppercase">Clock Out</p>
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

        @if($item->total_hours)
        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
            <span class="text-muted">Total Hours</span>
            <strong class="text-primary fs-5">{{ number_format($item->total_hours, 2) }}h</strong>
        </div>
        @endif

        <div class="row g-2 mb-3">
            <div class="col-6">
                <p class="text-muted small text-uppercase">Clock In Photo</p>
                @if($item->clock_in_photo)
                    <img src="{{ $item->clock_in_photo }}" class="w-100 rounded-3" style="max-height:160px;object-fit:cover;">
                @else
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted" style="height:100px;">No Image</div>
                @endif
            </div>
            <div class="col-6">
                <p class="text-muted small text-uppercase">Clock Out Photo</p>
                @if($item->clock_out_photo)
                    <img src="{{ $item->clock_out_photo }}" class="w-100 rounded-3" style="max-height:160px;object-fit:cover;">
                @else
                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center text-muted" style="height:100px;">No Image</div>
                @endif
            </div>
        </div>

    @elseif ($type === 'servicejob')

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1 small text-uppercase">Start Date</p>
                    <p class="fw-bold mb-0">{{ $item->formattedStartDate() }}</p>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-light rounded-3 p-3 text-center">
                    <p class="text-muted mb-1 small text-uppercase">End Date</p>
                    <p class="fw-bold mb-0">{{ $item->formattedEndDate() }}</p>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
            <span class="text-muted">Priority</span>
            <span class="badge bg-{{ $item->priority === 'low' ? 'success' : ($item->priority === 'medium' ? 'warning' : 'danger') }}">{{ ucfirst($item->priority) }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
            <span class="text-muted">Estimated Hours</span>
            <strong>{{ $item->estimated_hours ?? 0 }} hrs</strong>
        </div>

        <a href="{{ route('serviceJob.show', $item->id) }}" target="_blank" class="btn btn-outline-primary w-100 mb-3">
            <i class="ri-external-link-line me-1"></i> View Full Job Details
        </a>

    @else

        @if($item->checklist->items && $item->checklist->items->count())
            @foreach($item->checklist->items as $checkItem)
                @php $answer = $item->answers->firstWhere('checklist_item_id', $checkItem->id); @endphp
                <div class="border rounded p-3 mb-2">
                    <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $checkItem->type)) }}</div>
                    <strong>{{ $checkItem->question }}</strong>
                    @if($answer)
                        <div class="mt-2">
                            @if($answer->photo_path)
                                <img src="{{ asset($answer->photo_path) }}" class="img-fluid rounded mb-2" style="max-height:200px;object-fit:cover;">
                            @elseif($answer->answer)
                                <div class="bg-light rounded p-2 text-dark">{{ $answer->answer }}</div>
                            @endif
                            <small class="text-muted">Answered by {{ $answer->answeredBy->name ?? '' }}</small>
                        </div>
                    @else
                        <div class="mt-1 text-muted small fst-italic">No answer provided</div>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-muted">No checklist items</p>
        @endif

    @endif

    @if($status === 'pending')
        <hr>
        @if($type !== 'servicejob')
        <div class="mb-3">
            <label class="form-label text-muted">Rejection Reason (optional)</label>
            <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Enter reason if rejecting..."></textarea>
        </div>
        @endif

        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger flex-fill actionBtn" data-action="rejected">
                {{ $type === 'servicejob' ? 'Redo' : 'Reject' }}
            </button>
            <button class="btn btn-success flex-fill actionBtn" data-action="approved">
                {{ $type === 'servicejob' ? 'Confirm' : 'Approve' }}
            </button>
        </div>
    @else
        <div class="alert alert-{{ $status === 'approved' ? 'success' : 'danger' }} mt-3 mb-0">
            This item has been <strong>{{ $type === 'servicejob' ? ($status === 'approved' ? 'Confirmed' : 'Sent for Redo') : ucfirst($status) }}</strong>.
            @if(isset($item->rejection_reason) && $item->rejection_reason)
                <div class="mt-1">Reason: {{ $item->rejection_reason }}</div>
            @endif
        </div>
    @endif
</div>