<?php

namespace App\Imports;

use App\Models\SystemPart;
use App\Models\TeamPart;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TeamPricingImport implements SkipsOnError, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsErrors;

    protected $teamId;

    public function __construct($teamId)
    {
        $this->teamId = $teamId;
    }

    public function model(array $row)
    {
        // Find the system part
        $systemPart = SystemPart::where('manufacturer', $row['manufacturer'])
            ->where('model_number', $row['model_number'])
            ->first();

        if (! $systemPart) {
            return null;
        }

        // Prefer multiplier over static_price
        $multiplier = isset($row['multiplier']) && $row['multiplier'] !== '' ? $row['multiplier'] : null;
        $staticPrice = isset($row['static_price']) && $row['static_price'] !== '' ? $row['static_price'] : null;

        if ($multiplier !== null && $staticPrice !== null) {
            $staticPrice = null; // Use multiplier
        }

        return TeamPart::updateOrCreate(
            [
                'team_id' => $this->teamId,
                'system_part_id' => $systemPart->id,
            ],
            [
                'multiplier' => $multiplier,
                'static_price' => $staticPrice,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'manufacturer' => 'required|string',
            'model_number' => 'required|string',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
