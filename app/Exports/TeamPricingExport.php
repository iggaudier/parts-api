<?php

namespace App\Exports;

use App\Models\TeamPart;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeamPricingExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected $teamId;

    public function __construct($teamId)
    {
        $this->teamId = $teamId;
    }

    public function query()
    {
        return TeamPart::with('systemPart')
            ->where('team_id', $this->teamId)
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Part Type',
            'Manufacturer',
            'Model Number',
            'List Price',
            'Multiplier',
            'Static Price',
        ];
    }

    public function map($teamPart): array
    {
        return [
            $teamPart->systemPart->part_type,
            $teamPart->systemPart->manufacturer,
            $teamPart->systemPart->model_number,
            $teamPart->systemPart->list_price,
            $teamPart->multiplier ?? '',
            $teamPart->static_price ?? '',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
