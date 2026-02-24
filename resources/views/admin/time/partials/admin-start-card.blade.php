<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="ri-calendar-check-line me-1 text-success"></i> Today's Jobs
        </h5>
    </div>
    <div class="card-body p-3">
        @forelse($todayAssignments as $assignment)
        <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2">
            <div>
                <div class="fw-semibold">{{ $assignment->job->job_title }}</div>
                <div class="text-muted small">{{ $assignment->job->job_id }}</div>
                @if($assignment->start_time)
                    <div class="text-muted small">
                        {{ \Carbon\Carbon::parse($assignment->start_time)->format('h:i A') }}
                        @if($assignment->end_time) – {{ \Carbon\Carbon::parse($assignment->end_time)->format('h:i A') }} @endif
                    </div>
                @endif
            </div>
            <button type="button"
                class="btn btn-sm btn-primary admin-clockin-btn"
                data-assignment-id="{{ $assignment->id }}"
                data-job-title="{{ $assignment->job->job_title }}">
                <i class="ri-shield-user-line me-1"></i> Clock In As Admin
            </button>
        </div>
        @empty
        <p class="text-muted text-center py-3 mb-0">No jobs assigned for today.</p>
        @endforelse
    </div>
</div>