<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Role check methods
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function isTeamAdmin(): bool
    {
        return $this->role === 'team_admin';
    }

    public function isTeamMember(): bool
    {
        return $this->role === 'team_member';
    }

    // Scope for filtering by role
    public function scopeSystemAdmins($query)
    {
        return $query->where('role', 'system_admin');
    }

    public function scopeTeamAdmins($query)
    {
        return $query->where('role', 'team_admin');
    }

    public function scopeTeamMembers($query)
    {
        return $query->where('role', 'team_member');
    }
}
