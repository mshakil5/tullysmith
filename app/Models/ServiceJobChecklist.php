<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceJobChecklist extends Model
{
    protected $guarded = [];

    public function serviceJob()
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function answers()
    {
        return $this->hasMany(ChecklistAnswer::class);
    }
}
