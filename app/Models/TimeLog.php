<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'clock_in_at'  => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function job()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function assignment()
    {
        return $this->belongsTo(JobAssignment::class, 'job_assignment_id');
    }

    public function getTotalHoursFormattedAttribute()
    {
        if (!$this->clock_out_at) return 'Active';
        return number_format($this->total_hours, 1) . 'h';
    }

    public function getClockInTimeAttribute()
    {
        return $this->clock_in_at ? $this->clock_in_at->format('h:i A') : '-';
    }

    public function getClockOutTimeAttribute()
    {
        return $this->clock_out_at ? $this->clock_out_at->format('h:i A') : '-';
    }
}