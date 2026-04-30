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
  .text-success { color: #059669; }
  .text-dark { color: #1f2937; }

  /* ── Amount Banner ── */
  .words-banner {
    border: 1px solid #6ee7b7;
    border-radius: 8px;
    background: #ecfdf5;
    padding: 16px 20px;
    margin-top: 20px;
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
        <div class="report-title">EXPENSE REPORT</div>
        <div class="generated">Generated: {{ $generated_at }}</div>
      </td>
      <td class="header-right">
        <div class="meta-box">
          <div class="meta-row">
            <span class="meta-label">Date:</span>
            <span class="meta-value">{{ $label }}</span>
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

{{-- ── Expense List Table ── --}}
<div class="card-wrap">
  <div class="section-header">
    <span class="section-title">Expense List</span>
  </div>
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:35px;">#</th>
        <th>DATE</th>
        @if(!$jobFiltered)<th>JOB</th>@endif
        <th>TITLE</th>
        <th class="txt-right">AMOUNT</th>
      </tr>
    </thead>
    <tbody>
      @php $prevDate = null; @endphp
      @forelse($expenses as $i => $e)
        <tr class="{{ $e['date'] !== $prevDate && $i > 0 ? 'date-separator' : '' }}">
          <td class="muted txt-center">{{ $i + 1 }}</td>
          <td class="fw text-dark">{{ $e['date'] !== $prevDate ? $e['date'] : '' }}</td>
          @if(!$jobFiltered)
            <td>
              <div class="fw text-dark">{{ $e['job'] }}</div>
              <div class="muted">{{ $e['job_id'] }}</div>
            </td>
          @endif
          <td>{{ $e['title'] }}</td>
          <td class="txt-right fw text-success">£{{ $e['amount'] }}</td>
        </tr>
        @php $prevDate = $e['date']; @endphp
      @empty
        <tr>
          <td colspan="{{ 5 }}" class="txt-center muted" style="padding:30px;">
            No records for this period
          </td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="{{ 4 - ($jobFiltered ? 1 : 0) }}" class="txt-right">Total Amount</td>
        <td class="txt-right">£{{ $total_amount }}</td>
      </tr>
    </tfoot>
  </table>
</div>

{{-- ── Amount in Words ── --}}
@if(!empty($total_words))
<div class="words-banner">
  <div class="words-label">Total Amount in Words</div>
  <div class="words-text">{{ $total_words }}</div>
</div>
@endif

<div class="footer">
  {{ $company->company_name ?? 'Company Name' }} | Expense Report | {{ $label }}
</div>

</body>
</html>