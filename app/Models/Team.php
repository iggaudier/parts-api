<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function teamParts()
    {
        return $this->hasMany(TeamPart::class);
    }

    public function systemParts()
    {
        return $this->belongsToMany(SystemPart::class, 'team_parts')
            ->withPivot('multiplier', 'static_price', 'team_price')
            ->withTimestamps();
    }

    public function teamAdmins()
    {
        return $this->users()->where('role', 'team_admin');
    }

    public function teamMembers()
    {
        return $this->users()->where('role', 'team_member');
    }
}
