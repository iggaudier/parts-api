<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'system_part_id',
        'multiplier',
        'static_price',
        'team_price', // MUST be provided explicitly
    ];

    protected $casts = [
        'multiplier' => 'decimal:4',
        'static_price' => 'decimal:2',
        'team_price' => 'decimal:2',
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function systemPart()
    {
        return $this->belongsTo(SystemPart::class);
    }
}
