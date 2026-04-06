<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'content', 'priority', 'service_job_id', 'expires_at', 'status'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'status'     => 'boolean',
    ];

    public function job()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }
}