@extends('admin.pages.master')
@section('title', 'Timesheets')

@section('content')
<div class="container" style="max-width:680px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('time.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ri-arrow-left-line"></i>
            </a>
            <h4 class="mb-0">Timesheets</h4>
        </div>
        <a href="{{ route('time.export', ['mode' => $mode, 'offset' => $offset]) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="ri-download-line"></i> Export CSV
        </a>
    </div>

    <div class="btn-group w-100 mb-3" role="group">
        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
        <a href="{{ route('time.timesheet', ['mode' => $key, 'offset' => 0]) }}"
           class="btn btn-sm {{ $mode === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="{{ route('time.timesheet', ['mode' => $mode, 'offset' => $offset - 1]) }}"
           class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-s-line"></i></a>
        <strong style="font-size:0.95rem;">{{ $label }}</strong>
        <a href="{{ route('time.timesheet', ['mode' => $mode, 'offset' => $offset + 1]) }}"
           class="btn btn-sm btn-outline-secondary {{ $offset >= 0 ? 'disabled' : '' }}">
            <i class="ri-arrow-right-s-line"></i>
        </a>
    </div>

    <div class="card border-0 text-white mb-4" style="background:linear-gradient(135deg,#2d4a8a,#405189);border-radius:16px;">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <p class="mb-1 opacity-75" style="font-size:0.8rem;">Total Hours</p>
                <h2 class="fw-bold mb-0">{{ number_format($totalHours, 1) }}<small class="fs-6 fw-normal opacity-75">h</small></h2>
            </div>
            <div class="rounded-3 d-flex align-items-center justify-content-center"
                 style="width:52px;height:52px;background:rgba(255,255,255,0.15);">
                <i class="ri-time-line fs-4"></i>
            </div>
        </div>
    </div>

    @if($breakdown->isEmpty())
        <p class="text-muted text-center py-4">No entries for this period.</p>
    @else
        <p class="text-uppercase fw-semibold text-muted mb-2" style="font-size:0.72rem;letter-spacing:0.07em;">Daily Breakdown</p>

        @foreach($breakdown as $date => $dayLogs)
            @php
                $dayTotal = $dayLogs->whereNotNull('clock_out_at')->sum('total_hours');
                $dateObj  = \Carbon\Carbon::parse($date);
            @endphp
            <div class="card border-0 shadow-sm mb-2">
                <div class="card-body p-3">
                    {{-- Day header --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold" style="font-size:0.9rem;">{{ $dateObj->format('l, M d') }}</span>
                        <span class="fw-bold text-primary" style="font-size:0.9rem;">{{ number_format($dayTotal, 1) }}h</span>
                    </div>

                    {{-- Entries for this day --}}
                    @foreach($dayLogs as $log)
                    <div class="d-flex align-items-start gap-3 {{ !$loop->last ? 'mb-3 pb-3 border-bottom' : '' }}">
                        {{-- Clock-in photo --}}
                        <div class="flex-shrink-0 d-flex gap-1">
                            @if($log->clock_in_photo)
                                <img src="{{ $log->clock_in_photo }}" alt="In"
                                     class="rounded-2"
                                     style="width:40px;height:40px;object-fit:cover;cursor:pointer;"
                                     data-bs-toggle="modal" data-bs-target="#photoModal"
                                     data-src="{{ $log->clock_in_photo }}" data-label="Clock In — {{ $log->job->job_title }}">
                            @else
                                <div class="rounded-2 bg-light d-flex align-items-center justify-content-center text-secondary"
                                     style="width:40px;height:40px;font-size:1rem;">
                                    <i class="ri-user-line"></i>
                                </div>
                            @endif
                            @if($log->clock_out_photo)
                                <img src="{{ $log->clock_out_photo }}" alt="Out"
                                     class="rounded-2"
                                     style="width:40px;height:40px;object-fit:cover;cursor:pointer;opacity:0.75;"
                                     data-bs-toggle="modal" data-bs-target="#photoModal"
                                     data-src="{{ $log->clock_out_photo }}" data-label="Clock Out — {{ $log->job->job_title }}">
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-semibold" style="font-size:0.85rem;">{{ $log->job->job_title ?? '—' }}</p>
                            @if($log->clock_out_at)
                                <p class="mb-0 text-muted" style="font-size:0.75rem;">
                                    {{ $log->clock_in_time }} – {{ $log->clock_out_time }}
                                </p>
                            @else
                                <span class="badge bg-success-subtle text-success" style="font-size:0.7rem;">Active</span>
                            @endif
                            @if($log->location_note && !in_array($log->location_note, ['location_check_failed']))
                                <div>
                                    <span class="badge {{ $log->location_note === 'location_verified' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" style="font-size:0.65rem;">
                                        <i class="ri-map-pin-line"></i>
                                        {{ $log->location_note === 'location_verified' ? 'Location verified' : $log->location_note }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Hours --}}
                        <div class="text-end flex-shrink-0">
                            <span class="fw-bold" style="font-size:0.88rem;">
                                {{ $log->clock_out_at ? number_format($log->total_hours, 1) . 'h' : '—' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

</div>

<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="photoModalLabel">Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2">
                <img id="photoModalImg" src="" class="w-100 rounded-2" />
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(function () {
    $(document).on('click', '[data-bs-target="#photoModal"]', function () {
        $('#photoModalImg').attr('src', $(this).data('src'));
        $('#photoModalLabel').text($(this).data('label'));
    });
});
</script>
@endsection