<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

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
    min-width: 65px;
    color: #374151;
  }

  .meta-value {
    font-size: 11px;
    font-weight: 400;
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
  .txt-right { text-align: right; }
  .txt-center { text-align: center; }
  .fw { font-weight: 700; }
  .muted { color: #9ca3af; font-size: 10px; }
  .text-dark { color: #1f2937; }

  /* ── Badges ── */
  .badge-ci   { background: #10b981; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
  .badge-co   { background: #f59e0b; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
  .badge-both { background: #0ea5e9; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
  .badge-yes  { background: #22c55e; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
  .badge-no   { background: #ef4444; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }
  .badge-photo{ background: #0ea5e9; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; }

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
        @if(!empty($company->company_logo))
          <img src="{{ public_path('uploads/company/' . $company->company_logo) }}" class="logo" alt="">
        @endif
        <div class="report-title">CHECKLIST REPORT</div>
        <div class="generated">Generated: {{ $generated_at }}</div>
      </td>
      <td class="header-right">
        <div class="meta-box">
          <div class="meta-row">
            <span class="meta-label">Date:</span>
            <span class="meta-value">{{ $label }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Worker:</span>
            <span class="meta-value">{{ $worker }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Job:</span>
            <span class="meta-value">{{ $job }}</span>
          </div>
        </div>
      </td>
    </tr>
  </table>
</div>

{{-- ── Checklist Answers Table ── --}}
<div class="card-wrap">
  <div class="section-header">
    <span class="section-title">Checklist Answers</span>
  </div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:35px;">#</th>
        <th>DATE</th>
        <th>TIME</th>
        @if(!$workerFiltered)<th>WORKER</th>@endif
        @if(!$jobFiltered)<th>JOB</th>@endif
        <th>CHECKLIST</th>
        <th>QUESTION</th>
        <th>ANSWER</th>
      </tr>
    </thead>
    <tbody>
      @php $prevDate = null; @endphp
      @forelse($answers as $i => $a)
        <tr class="{{ $a['date'] !== $prevDate && $i > 0 ? 'date-separator' : '' }}">
          <td class="muted txt-center">{{ $i + 1 }}</td>
          <td class="fw text-dark">{{ $a['date'] !== $prevDate ? $a['date'] : '' }}</td>
          <td class="muted">{{ $a['time'] }}</td>
          @if(!$workerFiltered)<td>{{ $a['worker'] }}</td>@endif
          @if(!$jobFiltered)
            <td>
              <div class="fw text-dark">{{ $a['job'] }}</div>
              <div class="muted">{{ $a['job_id'] }}</div>
            </td>
          @endif
          <td>
            <div class="fw text-dark">{{ $a['checklist'] }}</div>
            @php $showAt = $a['show_at_raw'] ?? 'both'; @endphp
            @if($showAt === 'clock_in')
              <span class="badge-ci">Clock In</span>
            @elseif($showAt === 'clock_out')
              <span class="badge-co">Clock Out</span>
            @else
              <span class="badge-both">Both</span>
            @endif
          </td>
          <td>{{ $a['question'] }}</td>
          <td>
            @if($a['type'] === 'photo')
              <span class="badge-photo">Photo</span>
            @elseif($a['type'] === 'yes_no')
              @if(strtolower($a['answer'] ?? '') === 'yes')
                <span class="badge-yes">Yes</span>
              @else
                <span class="badge-no">No</span>
              @endif
            @else
              {{ $a['answer'] ?? '—' }}
            @endif
          </td>
        </tr>
        @php $prevDate = $a['date']; @endphp
      @empty
        <tr>
          <td colspan="{{ 8 - ($workerFiltered ? 1 : 0) - ($jobFiltered ? 1 : 0) }}"
              class="txt-center muted" style="padding:30px;">
            No records for this period
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="footer">
  {{ $company->company_name ?? 'Company Name' }} | Checklist Report | {{ $label }}
</div>

</body>
</html>