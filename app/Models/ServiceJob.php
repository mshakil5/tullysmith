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

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
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
        return $this->start_datetime ? Carbon::parse($this->start_datetime)->format('d M Y H:i') : 'N/A';
    }

    public function formattedEndDate()
    {
        return $this->end_datetime ? Carbon::parse($this->end_datetime)->format('d M Y H:i') : 'N/A';
    }
}