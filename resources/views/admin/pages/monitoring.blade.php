@extends('admin.pages.master')
@section('title','System Monitoring')
@section('content')
<div class="container-fluid py-3">
<h3>System Monitoring</h3><p class="text-muted">Recorded incidents, not a live uptime check. Last 24 hours summary; slow requests take 2 seconds or more.</p>
<div class="row">@foreach(['timeout'=>'Mobile timeouts','network'=>'Network failures','http_error'=>'HTTP errors','slow_request'=>'Slow API requests','geofence_rejected'=>'Geofence rejections','gps_error'=>'GPS failures','invalid_postcode'=>'Invalid postcodes','error'=>'Other mobile errors'] as $key=>$label)
<div class="col-md-3 mb-3"><div class="card p-3"><span>{{ $label }}</span><h3>{{ $counts[$key] ?? 0 }}</h3></div></div>@endforeach</div>
<div class="card p-3"><form method="get" class="d-flex gap-2 mb-3"><select name="source" class="form-select"><option value="">All sources</option>@foreach(['mobile','server'] as $source)<option value="{{ $source }}" @selected(request('source')===$source)>{{ ucfirst($source) }}</option>@endforeach</select><input class="form-control" name="user_id" type="number" placeholder="Employee ID" value="{{ request('user_id') }}"><button class="btn btn-primary">Filter / Refresh</button></form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Time</th><th>Employee</th><th>Source</th><th>Operation</th><th>Issue</th><th>HTTP</th><th>ms</th><th>Platform / version</th><th>Reference</th></tr></thead><tbody>
@forelse($events as $e)<tr><td>{{ $e->created_at }}</td><td>{{ $e->employee ?? 'Unknown' }} ({{ $e->user_id ?? '-' }})</td><td>{{ $e->source }}</td><td>{{ $e->operation }}</td><td>{{ $e->kind }}</td><td>{{ $e->status }}</td><td>{{ $e->duration_ms }}</td><td>{{ $e->platform }} {{ $e->version }}</td><td>{{ $e->reference }}</td></tr>@empty<tr><td colspan="9">No recorded incidents. This does not establish that all services are healthy.</td></tr>@endforelse
</tbody></table></div>{{ $events->links() }}</div></div>
@endsection
