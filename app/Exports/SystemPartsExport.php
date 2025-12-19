<?php

namespace App\Exports;

use App\Models\SystemPart;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SystemPartsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = SystemPart::query();

        if (isset($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active']);
        }

        if (isset($this->filters['part_type'])) {
            $query->where('part_type', $this->filters['part_type']);
        }

        if (isset($this->filters['manufacturer'])) {
            $query->where('manufacturer', $this->filters['manufacturer']);
        }

        return $query->orderBy('manufacturer')->orderBy('model_number');
    }

    public function headings(): array
    {
        return [
            'Active',
            'Part Type',
            'Manufacturer',
            'Model Number',
            'List Price',
        ];
    }

    public function map($part): array
    {
        return [
            $part->is_active ? 'Y' : 'N',
            $part->part_type,
            $part->manufacturer,
            $part->model_number,
            $part->list_price,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
