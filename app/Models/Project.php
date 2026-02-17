<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'address', 'latitude', 'longitude', 'project_area', 'client_id'];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
