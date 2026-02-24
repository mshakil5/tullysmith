<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ServiceJob extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function assignments()
    {
        return $this->hasMany(JobAssignment::class, 'service_job_id')->with('worker')->latest();
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function documents()
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function formattedStartDate()
    {
        return $this->start_date ? Carbon::parse($this->start_date)->format('d M Y') : '';
    }

    public function formattedEndDate()
    {
        return $this->end_date ? Carbon::parse($this->end_date)->format('d M Y') : '';
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'service_job_id')->with('worker')->latest();
    }
}