<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #fff;
            padding: 40px 30px;
        }

        /* ── Header ── */
        .header-wrapper {
            width: 100%;
            border-bottom: 2px solid #1a2d52;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            width: 40%;
            vertical-align: bottom;
            text-align: right;
        }

        .logo {
            height: 45px;
            margin-bottom: 8px;
            display: block;
        }

        .report-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a2d52;
            letter-spacing: 2px;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .generated {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .meta-box {
            display: inline-block;
            text-align: left;
        }

        .meta-row {
            margin-bottom: 4px;
            line-height: 1.6;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            min-width: 90px;
            color: #374151;
        }

        .meta-value {
            font-size: 11px;
            font-weight: 700;
            color: #1f2937;
        }

        /* ── Section ── */
        .card-wrap {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            background: #fff;
        }

        .section-header {
            padding: 12px 16px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #1a2d52;
        }

        /* ── Table ── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table thead th {
            background: #1e3a5f;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border: none;
        }

        .report-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .report-table tbody td {
            padding: 10px 12px;
            font-size: 11px;
            vertical-align: middle;
            color: #374151;
        }

        .report-table tfoot td {
            background: #1e3a5f;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            padding: 10px 12px;
            border: none;
        }

        .date-separator td {
            border-top: 2px solid #cbd5e1 !important;
            padding-top: 12px !important;
        }

        /* ── Utility ── */
        .txt-right {
            text-align: right !important;
        }

        .txt-center {
            text-align: center !important;
        }

        .fw {
            font-weight: 700;
        }

        .muted {
            color: #9ca3af;
            font-size: 10px;
        }

        .text-primary {
            color: #4f46e5;
        }

        .text-dark {
            color: #1f2937;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            margin-top: 20px;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- ── Header ── --}}
    <div class="header-wrapper">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if (!empty($company->company_logo))
                        <img src="{{ public_path('uploads/company/' . $company->company_logo) }}" class="logo"
                            alt="">
                    @endif
                    <div class="report-title">TIME REPORT</div>
                    <div class="generated">Generated: {{ $generated_at }}</div>
                </td>
                <td class="header-right">
                    <table style="border-collapse:collapse; margin-left:auto;">
                        <tr>
                            <td
                                style="font-size:11px;font-weight:700;color:#374151;padding:3px 10px 3px 0;white-space:nowrap;">
                                Date:</td>
                            <td style="font-size:11px;font-weight:700;color:#1f2937;padding:3px 0;white-space:nowrap;">
                                {{ $label }}</td>
                        </tr>
                        <tr>
                            <td
                                style="font-size:11px;font-weight:700;color:#374151;padding:3px 10px 3px 0;white-space:nowrap;">
                                Worker:</td>
                            <td style="font-size:11px;font-weight:700;color:#1f2937;padding:3px 0;white-space:nowrap;">
                                {{ $worker }}</td>
                        </tr>
                        <tr>
                            <td
                                style="font-size:11px;font-weight:700;color:#374151;padding:3px 10px 3px 0;white-space:nowrap;">
                                Job:</td>
                            <td style="font-size:11px;font-weight:700;color:#1f2937;padding:3px 0;white-space:nowrap;">
                                {{ $job }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Time Entries Table ── --}}
    <div class="card-wrap">
        <div class="section-header">
            <span class="section-title">Time Entries</span>
        </div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th>DATE</th>
                    @if (!$workerFiltered)
                        <th>WORKER</th>
                    @endif
                    @if (!$jobFiltered)
                        <th>JOB</th>
                    @endif
                    <th class="txt-center">CLOCK IN</th>
                    <th class="txt-center">CLOCK OUT</th>
                    <th class="txt-right">HOURS</th>
                </tr>
            </thead>
            <tbody>
                @php $prevDate = null; @endphp
                @forelse($logs as $i => $l)
                    <tr class="{{ $l['date'] !== $prevDate && $i > 0 ? 'date-separator' : '' }}">
                        <td class="muted txt-center">{{ $i + 1 }}</td>
                        <td class="fw text-dark">{{ $l['date'] }}</td>
                        @if (!$workerFiltered)
                            <td>{{ $l['worker'] }}</td>
                        @endif
                        @if (!$jobFiltered)
                            <td>
                                <div class="fw text-dark">{{ $l['job'] }}</div>
                                <div class="muted">{{ $l['job_id'] }}</div>
                            </td>
                        @endif
                        <td class="txt-center">{{ $l['clock_in'] }}</td>
                        <td class="txt-center">{{ $l['clock_out'] }}</td>
                        <td class="txt-right fw text-primary">{{ $l['hours'] }}h</td>
                    </tr>
                    @php $prevDate = $l['date']; @endphp
                @empty
                    <tr>
                        <td colspan="7" class="txt-center muted" style="padding:30px;">
                            No records for this period
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ 6 - ($workerFiltered ? 1 : 0) - ($jobFiltered ? 1 : 0) }}" class="txt-right">Total
                        Hours</td>
                    <td class="txt-right">{{ $total_hours }}h</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        {{ $company->company_name ?? 'Company Name' }} | Time Report | {{ $label }}
    </div>

</body>

</html>
