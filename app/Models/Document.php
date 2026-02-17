<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'service_job_id',
        'created_by',
        'type',
        'title',
        'file',
        'amount',
        'status',
    ];

    public function job()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}