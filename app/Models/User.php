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

    public function getUserRole()
    {
        return $this->roles()->first();
    }

    public function getCreationStatusAttribute()
    {
        $role = $this->getUserRole();
        return ($role && (strtolower($role->name) === 'admin' || strtolower($role->name) === 'super admin')) ? 'approved' : 'pending';
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
    }

    // $workers   = User::byRole('Worker')->get();
    // $supervisors  = User::byRole('Supervisor')->get();
    // $admins    = User::byRole('Admin')->get();
}
