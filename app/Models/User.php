<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function serviceJobs()
    {
        return $this->hasMany(ServiceJob::class, 'client_id');
    }

    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class, 'worker_id');
    }

    public function getUserRole()
    {
        return $this->roles()->first();
    }

    public function getCreationStatusAttribute()
    {
        $role = $this->getUserRole();
        return ($role && (strtolower($role->name) === 'super admin' || strtolower($role->name) === 'line manager')) ? 'approved' : 'pending';
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
    }

    // $workers   = User::byRole('Worker')->get();
    // $lineanagers  = User::byRole('Line Manager')->get();
    // $superAdmins    = User::byRole('Super Admin')->get();
}
