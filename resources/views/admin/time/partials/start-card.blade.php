<div class="card border-0 text-white mb-4" style="background:#405189;border-radius:16px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3 text-white">Start Your Day</h5>
        @if($todayAssignments->isEmpty())
            <p class="mb-0" style="opacity:0.7;font-size:0.85rem;">No jobs assigned for today.</p>
        @else
            @foreach($todayAssignments as $assignment)
            <div class="job-select-item border rounded-3 p-3 mb-2"
                 style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.25) !important;cursor:pointer;"
                 data-id="{{ $assignment->id }}"
                 data-start="{{ $assignment->start_time ? \Carbon\Carbon::parse($assignment->start_time)->format('H:i') : '' }}"
                 data-end="{{ $assignment->end_time ? \Carbon\Carbon::parse($assignment->end_time)->format('H:i') : '' }}">
                <p class="mb-0 fw-semibold text-white" style="font-size:0.88rem;">{{ $assignment->job->job_title }}</p>
                <p class="mb-0 text-white" style="opacity:0.75;font-size:0.75rem;">
                    {{ $assignment->job->job_id }}
                    @if($assignment->start_time) · {{ \Carbon\Carbon::parse($assignment->start_time)->format('h:i A') }} @endif
                    @if($assignment->end_time) – {{ \Carbon\Carbon::parse($assignment->end_time)->format('h:i A') }} @endif
                    @if($assignment->job->city) · {{ $assignment->job->city }} @endif
                </p>
            </div>
            @endforeach
        @endif
        <button class="btn w-100 mt-3 fw-semibold" id="clockInBtn" style="background:#fff;color:#405189;">
            <i class="ri-camera-line"></i> Clock In with Photo
        </button>
    </div>
</div>