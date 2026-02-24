<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistAnswer extends Model
{
    protected $fillable = [
        'service_job_checklist_id',
        'checklist_item_id',
        'answered_by',
        'answer',
        'photo_path',
    ];

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }
    public function answeredBy()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
