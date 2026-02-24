<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center p-3">
                <h4 class="fw-bold mb-0">{{ number_format($todayHours, 2) }}</h4>
                <p class="text-muted mb-0 fs-12">Today</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center p-3">
                <h4 class="fw-bold mb-0">{{ number_format($weekHours, 2) }}</h4>
                <p class="text-muted mb-0 fs-12">This Week</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center p-3">
                <h4 class="fw-bold mb-0">{{ number_format($monthHours, 2) }}</h4>
                <p class="text-muted mb-0 fs-12">This Month</p>
            </div>
        </div>
    </div>
</div>