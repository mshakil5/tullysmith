@extends('admin.pages.master')
@section('title', 'Timesheets')

@section('content')
<div class="container-fluid">

    <div class="row">

        <div class="col-xl-5 col-lg-6">
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    @role('Worker')
                    <a href="{{ route('time.index') }}" class="btn btn-soft-secondary btn-sm">
                        <i class="ri-arrow-left-line me-1"></i> Back
                    </a>
                    @endrole
                    <h4 class="mb-0">Timesheets</h4>
                </div>

                <a href="{{ route('time.export', ['mode' => $mode, 'offset' => $offset]) }}"
                   class="btn btn-soft-primary btn-sm">
                    <i class="ri-download-2-line me-1"></i> Export CSV
                </a>
            </div>

            @if(!$currentUser->hasRole('Worker'))
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <label class="form-label fw-medium">Select Worker</label>
                        <select name="worker_id" id="workerSelect" class="form-control select2"
                                onchange="window.location = '{{ route('time.timesheet') }}?worker_id=' + this.value + '&mode={{ $mode }}&offset={{ $offset }}'">
                            <option value="">-- Choose a worker --</option>
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}" {{ $workerId == $worker->id ? 'selected' : '' }}>
                                    {{ $worker->name }}
                                </option>
                            @endforeach
                        </select>

                        @if(!$workerId && $workers->isNotEmpty())
                            <div class="alert alert-info mt-3 mb-0 small py-2">
                                <i class="ri-information-line me-1"></i>
                                Please select a worker to view their timesheet.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="btn-group w-100 mb-4" role="group">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $lbl)
                    <a href="{{ route('time.timesheet', ['mode' => $key, 'offset' => 0]) . ($workerId ? '&worker_id=' . $workerId : '') }}"
                       class="btn btn-sm {{ $mode === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $lbl }}
                    </a>
                @endforeach
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('time.timesheet', ['mode' => $mode, 'offset' => $offset - 1]) . ($workerId ? '&worker_id=' . $workerId : '') }}"
                           class="btn btn-soft-secondary btn-icon btn-sm rounded-circle">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>

                        <div class="text-center">
                            <h5 class="mb-1 fw-semibold">{{ $label }}</h5>
                            <p class="text-muted mb-0 small">
                                {{ $start->format($mode === 'monthly' ? 'F Y' : 'M d, Y') }}
                                @if($mode !== 'monthly') — {{ $end->format('M d, Y') }} @endif
                            </p>
                        </div>

                        <a href="{{ route('time.timesheet', ['mode' => $mode, 'offset' => $offset + 1]) . ($workerId ? '&worker_id=' . $workerId : '') }}"
                           class="btn btn-soft-secondary btn-icon btn-sm rounded-circle {{ $offset >= 0 ? 'disabled opacity-50' : '' }}">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </div>

                    <div class="bg-soft-primary rounded p-3 text-center">
                        <p class="text-muted mb-1 small">Total Hours</p>
                        <h3 class="fw-bold mb-0">{{ number_format($totalHours, 2) }}<span class="fs-5 text-muted">h</span></h3>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-xl-7 col-lg-6">
            @if($selectedWorker)
                <div class="mb-3">
                    <h5 class="mb-1 fw-semibold">
                        {{ $selectedWorker->name ?? '—' }}'s Timesheet
                    </h5>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daily Breakdown</h5>
                    @if(!$breakdown->isEmpty())
                        <span class="badge bg-success px-3 py-2">
                            {{ number_format($totalHours, 2) }}h total
                        </span>
                    @endif
                </div>

                <div class="card-body">
                    @if($breakdown->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ri-calendar-close-line ri-2x d-block mb-3 opacity-50"></i>
                            No entries found for this period
                            @if(!$workerId && !$currentUser->hasRole('Worker'))
                                <div class="mt-2 small">Select a worker above to view their records.</div>
                            @endif
                        </div>
                    @else
                        @foreach($breakdown as $dateStr => $logs)
                            @php
                                $date = \Carbon\Carbon::parse($dateStr);
                                $dayTotal = $logs->sum(fn($log) => $log->total_hours ?? 0);
                            @endphp

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <h6 class="mb-0 fw-semibold">{{ $date->format('l, M jS') }}</h6>
                                    <span class="badge bg-primary px-3 py-2">
                                        {{ number_format($dayTotal, 2) }}h
                                    </span>
                                </div>

                                @foreach($logs as $log)
                                    <div class="d-flex align-items-start gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <div class="d-flex gap-2 flex-shrink-0">
                                            @if($log->clock_in_photo)
                                                <img src="{{ $log->clock_in_photo }}" class="rounded shadow-sm"
                                                     style="width:60px; height:60px; object-fit:cover; cursor:pointer;"
                                                     data-bs-toggle="modal" data-bs-target="#photoModal"
                                                     data-src="{{ $log->clock_in_photo }}"
                                                     data-label="Clock In — {{ $log->job?->job_title ?? 'Job' }}">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted"
                                                     style="width:60px; height:60px;">
                                                    <i class="ri-camera-off-line fs-4"></i>
                                                </div>
                                            @endif

                                            @if($log->clock_out_photo)
                                                <img src="{{ $log->clock_out_photo }}" class="rounded shadow-sm opacity-85"
                                                     style="width:60px; height:60px; object-fit:cover; cursor:pointer;"
                                                     data-bs-toggle="modal" data-bs-target="#photoModal"
                                                     data-src="{{ $log->clock_out_photo }}"
                                                     data-label="Clock Out — {{ $log->job?->job_title ?? 'Job' }}">
                                            @endif
                                        </div>

                                        <div class="flex-grow-1">
                                            <div class="fw-medium mb-1">{{ $log->job?->job_title ?? '—' }}</div>
                                            <div class="small text-muted">
                                                @if($log->clock_out_at)
                                                    {{ $log->clock_in_at->format('h:i A') }} – {{ $log->clock_out_at->format('h:i A') }}
                                                @else
                                                    <span class="badge bg-success-subtle text-success">Active</span>
                                                @endif
                                            </div>
                                            @if($log->location_note && $log->location_note !== 'location_check_failed')
                                                <span class="badge mt-1 {{ str_contains($log->location_note, 'verified') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} small">
                                                    <i class="ri-map-pin-line me-1"></i> {{ $log->location_note }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-end flex-shrink-0 fw-semibold" style="min-width: 70px;">
                                            {{ $log->clock_out_at ? number_format($log->total_hours, 2) . 'h' : '—' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>

<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="photoModalImg" src="" class="img-fluid rounded" alt="Proof photo">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(function(){
    $('[data-bs-toggle="modal"][data-bs-target="#photoModal"]').on('click', function(){
        $('#photoModalImg').attr('src', $(this).data('src'));
        $('#photoModalLabel').text($(this).data('label') || 'Photo');
    });
});
</script>
@endsection