<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Start Your Day</h5>
    </div>
    <div class="card-body">
        @if($todayAssignments->isEmpty())
            <div class="alert alert-warning mb-3">
                <i class="ri-information-line me-1"></i> No jobs assigned for today.
            </div>
        @else
            @foreach($todayAssignments as $assignment)
            <div class="job-select-item border rounded p-3 mb-2"
                 style="cursor:pointer;transition:border-color 0.15s,background 0.15s;"
                 data-id="{{ $assignment->id }}"
                 data-start="{{ $assignment->start_time ? \Carbon\Carbon::parse($assignment->start_time)->format('H:i') : '' }}"
                 data-end="{{ $assignment->end_time ? \Carbon\Carbon::parse($assignment->end_time)->format('H:i') : '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-semibold">{{ $assignment->job->job_title }}</h6>
                        <p class="text-muted mb-0 fs-12">
                            {{ $assignment->job->job_id }}
                            @if($assignment->start_time) · {{ \Carbon\Carbon::parse($assignment->start_time)->format('h:i A') }} @endif
                            @if($assignment->end_time) – {{ \Carbon\Carbon::parse($assignment->end_time)->format('h:i A') }} @endif
                            @if($assignment->job->city) · {{ $assignment->job->city }} @endif
                        </p>
                    </div>
                    <span class="badge badge-soft-{{ $assignment->job->status === 'active' ? 'success' : 'warning' }}">
                        {{ ucfirst($assignment->job->status) }}
                    </span>
                </div>
            </div>
            @endforeach
        @endif
        <button class="btn btn-primary w-100 mt-2" id="clockInBtn">
            <i class="ri-camera-line me-1"></i> Clock In with Photo
        </button>
    </div>
</div>