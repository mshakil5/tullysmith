<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ChecklistAnswer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'service_job_checklist_id',
        'checklist_item_id',
        'answered_by',
        'answer',
        'photo_path',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logExcept(['created_at', 'updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return "ChecklistAnswer {$this->id} was {$eventName} by " 
                    . (auth()->user()?->name ?? 'system');
            })
            ->useLogName('checklist_answer');
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    public function assignment()
    {
        return $this->belongsTo(ServiceJobChecklist::class, 'service_job_checklist_id');
    }

    public function serviceJobChecklist()
    {
        return $this->belongsTo(ServiceJobChecklist::class, 'service_job_checklist_id');
    }

    public function answeredBy()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
