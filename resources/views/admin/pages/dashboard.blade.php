@extends('admin.pages.master')
@section('title', 'Dashboard')

@push('css')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
    <style>
        .fc {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
        }

        .fc-toolbar-title {
            font-size: 1rem !important;
            font-weight: 700;
        }

        .fc-button {
            background: #405189 !important;
            border-color: #405189 !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            box-shadow: none !important;
        }

        .fc-button:hover {
            background: #333f6b !important;
            border-color: #333f6b !important;
        }

        .fc-daygrid-day-frame {
            min-height: 120px;
        }

        .fc-event {
            cursor: pointer;
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border-left: 4px solid #405189 !important;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 6px;
        }

        /* Modal styling */
        #assignmentModal .modal-body {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        #assignmentModal .modal-header {
            background: #405189;
            color: #fff;
        }

        #assignmentModal .modal-title {
            font-weight: 700;
        }

        #assignmentModal p {
            margin-bottom: 0.5rem;
        }

        #assignmentModal span {
            font-weight: 600;
            color: #1e293b;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mb-3 d-flex justify-content-between align-items-center">
        <h4>Dashboard</h4>
        <a href="{{ route('jobAssignment.index') }}" class="btn btn-primary">Assign Job</a>
    </div>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Staff Assigned</p>
                            </div>
                            <div class="flex-shrink-0">
                                <h5 class="text-success fs-14 mb-0">
                                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +0 %
                                </h5>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $totalStaff }}</h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-soft-primary rounded fs-3">
                                    <i class="bx bx-user text-primary"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Active Jobs</p>
                            </div>
                            <div class="flex-shrink-0">
                                <h5 class="text-success fs-14 mb-0">
                                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +0 %
                                </h5>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $activeJobs }}</h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-soft-success rounded fs-3">
                                    <i class="bx bx-briefcase-alt-2 text-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Pending Jobs</p>
                            </div>
                            <div class="flex-shrink-0">
                                <h5 class="text-danger fs-14 mb-0">
                                    <i class="ri-arrow-right-down-line fs-13 align-middle"></i> -0 %
                                </h5>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $pendingJobs }}</h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-soft-warning rounded fs-3">
                                    <i class="bx bx-time-five text-warning"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-animate shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Today's Assignments</p>
                            </div>
                            <div class="flex-shrink-0">
                                <h5 class="text-info fs-14 mb-0">
                                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +0 %
                                </h5>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <div>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $todaysAssignments }}</h4>
                            </div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-soft-info rounded fs-3">
                                    <i class="bx bx-calendar-check text-info"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div id="calendar"></div>
    </div>

    <div id="assignmentModal" class="modal fade" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h6 class="fw-semibold mb-2">Job</h6>
                    <p class="text-muted" id="modalJob"></p>

                    <h6 class="fw-semibold mb-2">Worker</h6>
                    <p class="text-muted" id="modalWorker"></p>

                    <h6 class="fw-semibold mb-2">Date</h6>
                    <p class="text-muted" id="modalDate"></p>

                    <h6 class="fw-semibold mb-2">Time</h6>
                    <p class="text-muted" id="modalTime"></p>

                    <h6 class="fw-semibold mb-2">Note</h6>
                    <p class="text-muted" id="modalNote"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <!-- Optional: Add an Edit button if needed -->
                    <!-- <button type="button" class="btn btn-primary">Edit Assignment</button> -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script>
        $(function() {
            var assignments = @json($assignments);

            function formatTime12h(start, end) {
                let format = function(t) {
                    return t ? new Date('1970-01-01T' + t).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }) : '';
                };
                return start ? format(start) + (end ? ' — ' + format(end) : '') : '';
            }

            var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'today next'
                },
                height: 'auto',
                events: assignments,
                eventClick: function(info) {
                    var p = info.event.extendedProps;
                    $('#modalJob').text(p.job_title + ' (' + p.job_id + ')');
                    $('#modalWorker').text(p.worker_name);
                    let d = new Date(p.assigned_date);
                    let formattedDate = d.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                    $('#modalDate').text(formattedDate);
                    $('#modalTime').text(formatTime12h(p.start_time, p.end_time));
                    $('#modalNote').text(p.note ?? '-');
                    $('#assignmentModal').modal('show');
                }
            });

            calendar.render();
        });
    </script>
@endsection