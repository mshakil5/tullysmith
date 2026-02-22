<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JobAssignment extends Model
{
    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->assigned_date)->format('d F Y');
    }

    public function formatTime($time)
    {
        return $time ? Carbon::parse($time)->format('h:i A') : '';
    }
}