<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_type',
        'manufacturer',
        'model_number',
        'list_price',
        'is_active',
    ];

    protected $casts = [
        'list_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function teamParts()
    {
        return $this->hasMany(TeamPart::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_parts')
            ->withPivot('multiplier', 'static_price', 'team_price')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPartType($query, $partType)
    {
        return $query->where('part_type', $partType);
    }

    public function scopeByManufacturer($query, $manufacturer)
    {
        return $query->where('manufacturer', $manufacturer);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('model_number', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%")
                ->orWhere('part_type', 'like', "%{$search}%");
        });
    }
}
