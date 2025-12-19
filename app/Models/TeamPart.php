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
        'team_price',
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

    // Calculate team price based on multiplier or static price
    public static function calculateTeamPrice($listPrice, $multiplier = null, $staticPrice = null)
    {
        if ($staticPrice !== null) {
            return $staticPrice;
        }

        if ($multiplier !== null) {
            return round($listPrice * $multiplier, 2);
        }

        return $listPrice;
    }

    // Observer to auto-calculate team_price
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($teamPart) {
            if ($teamPart->systemPart) {
                $teamPart->team_price = self::calculateTeamPrice(
                    $teamPart->systemPart->list_price,
                    $teamPart->multiplier,
                    $teamPart->static_price
                );
            }
        });

        static::updating(function ($teamPart) {
            if ($teamPart->systemPart) {
                $teamPart->team_price = self::calculateTeamPrice(
                    $teamPart->systemPart->list_price,
                    $teamPart->multiplier,
                    $teamPart->static_price
                );
            }
        });
    }
}
