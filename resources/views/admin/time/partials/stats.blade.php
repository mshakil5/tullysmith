<div class="row g-3 mb-4" id="statsRow">
    <div class="col-4">
        <div class="card border-0 text-center shadow-sm">
            <div class="card-body p-3">
                <h4 class="fw-bold mb-0 text-dark">{{ number_format($todayHours, 1) }}</h4>
                <p class="text-muted mb-0" style="font-size:0.75rem;">Today</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 text-center shadow-sm">
            <div class="card-body p-3">
                <h4 class="fw-bold mb-0 text-dark">{{ number_format($weekHours, 1) }}</h4>
                <p class="text-muted mb-0" style="font-size:0.75rem;">This Week</p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 text-center shadow-sm">
            <div class="card-body p-3">
                <h4 class="fw-bold mb-0 text-dark">{{ number_format($monthHours, 1) }}</h4>
                <p class="text-muted mb-0" style="font-size:0.75rem;">This Month</p>
            </div>
        </div>
    </div>
</div>