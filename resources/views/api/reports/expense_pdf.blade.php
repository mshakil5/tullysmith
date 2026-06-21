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
            vertical-align: top;
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

        .txt-right {
            text-align: right;
        }

        .txt-center {
            text-align: center;
        }

        .fw {
            font-weight: 700;
        }

        .muted {
            color: #9ca3af;
            font-size: 10px;
        }

        .text-success {
            color: #059669;
        }

        .text-dark {
            color: #1f2937;
        }

        .words-banner {
            border: 1px solid #6ee7b7;
            border-radius: 8px;
            background: #ecfdf5;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .words-label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .words-text {
            font-size: 13px;
            font-weight: 700;
            color: #065f46;
        }

        .attachments-wrap {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            background: #fff;
        }

        .att-grid-table {
            width: 100%;
            border-collapse: collapse;
        }

        .att-cell {
            width: 25%;
            padding: 8px;
            vertical-align: top;
            border: none;
        }

        .att-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            text-align: center;
        }

        .att-img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            display: block;
        }

        .att-pdf-icon {
            width: 100%;
            height: 130px;
            background: #fff5f5;
            text-align: center;
            vertical-align: middle;
            display: table-cell;
            font-size: 10px;
            color: #dc2626;
            font-weight: 700;
        }

        .att-amount {
            padding: 6px 4px;
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

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

    {{-- Header --}}
    <div class="header-wrapper">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if (!empty($company->company_logo))
                        <img src="{{ public_path('uploads/company/' . $company->company_logo) }}" class="logo"
                            alt="">
                    @endif
                    <div class="report-title">EXPENSE REPORT</div>
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
                                Job:</td>
                            <td style="font-size:11px;font-weight:700;color:#1f2937;padding:3px 0;white-space:nowrap;">
                                {{ $job }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Expense List Table --}}
    <div class="card-wrap">
        <div class="section-header">
            <span class="section-title">Expense List</span>
        </div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:35px;">#</th>
                    <th>DATE</th>
                    @if (!$jobFiltered)
                        <th>JOB</th>
                    @endif
                    <th>TITLE</th>
                    <th style="width:70px; text-align:center">FILE</th>
                    <th style="text-align:right">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @php $prevDate = null; @endphp
                @forelse($expenses as $i => $e)
                    <tr class="{{ $e['date'] !== $prevDate && $i > 0 ? 'date-separator' : '' }}">
                        <td class="muted txt-center">{{ $i + 1 }}</td>
                        <td class="fw text-dark">{{ $e['date'] }}</td>
                        @if (!$jobFiltered)
                            <td>
                                <div class="fw text-dark">{{ $e['job_id'] }}</div>
                            </td>
                        @endif
                        <td>{{ $e['title'] }}</td>
                        <td class="txt-center">
                            @if (!empty($e['file_base64']))
                                <img src="{{ $e['file_base64'] }}"
                                    style="height:38px;width:52px;object-fit:cover;border-radius:4px;">
                            @elseif(isset($e['file_ext']) && $e['file_ext'] === 'pdf')
                                <span style="font-size:9px;color:#dc2626;font-weight:700;">PDF</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="txt-right fw text-success">£{{ $e['amount'] }}</td>
                    </tr>
                    @php $prevDate = $e['date']; @endphp
                @empty
                    <tr>
                        <td colspan="{{ $jobFiltered ? 5 : 6 }}" class="txt-center muted" style="padding:30px;">
                            No records for this period
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $jobFiltered ? 4 : 5 }}" class="txt-right">Total Amount</td>
                    <td class="txt-right">£{{ $total_amount }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Amount in Words --}}
    @if (!empty($total_words))
        <div class="words-banner">
            <div class="words-label">Total Amount in Words</div>
            <div class="words-text">{{ $total_words }}</div>
        </div>
    @endif

    {{-- Attachments Grid --}}
    @php
        $attachments = collect($expenses)->filter(
            fn($e) => !empty($e['file_base64']) || (isset($e['file_ext']) && $e['file_ext'] === 'pdf'),
        );
    @endphp

    @if ($attachments->count())
        <div class="attachments-wrap">
            <div class="section-header">
                <span class="section-title">Expense Attachments</span>
            </div>
            <table class="att-grid-table">
                @foreach ($attachments->chunk(4) as $row)
                    <tr>
                        @foreach ($row as $e)
                            <td class="att-cell">
                                <div class="att-card">
                                    @if (!empty($e['file_base64']))
                                        <img src="{{ $e['file_base64'] }}" class="att-img">
                                    @else
                                        <table style="width:100%;height:130px;">
                                            <tr>
                                                <td class="att-pdf-icon">PDF</td>
                                            </tr>
                                        </table>
                                    @endif
                                    <div class="att-amount">£{{ $e['amount'] }}</div>
                                </div>
                            </td>
                        @endforeach
                        @for ($f = $row->count(); $f < 4; $f++)
                            <td class="att-cell"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="footer">
        {{ $company->company_name ?? 'Company Name' }} | Expense Report | {{ $label }}
    </div>

</body>

</html>
