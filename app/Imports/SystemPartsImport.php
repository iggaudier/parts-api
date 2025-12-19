<?php

namespace App\Imports;

use App\Models\SystemPart;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SystemPartsImport implements SkipsOnError, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsErrors;

    public function model(array $row)
    {
        // Update if exists, create if not
        return SystemPart::updateOrCreate(
            [
                'manufacturer' => $row['manufacturer'],
                'model_number' => $row['model_number'],
            ],
            [
                'part_type' => $row['part_type'],
                'list_price' => $row['list_price'],
                'is_active' => $row['active'] ?? true,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'part_type' => 'required|string',
            'manufacturer' => 'required|string',
            'model_number' => 'required|string',
            'list_price' => 'required|numeric|min:0',
        ];
    }

    // Process in batches for performance
    public function batchSize(): int
    {
        return 500;
    }

    // Read file in chunks for memory efficiency
    public function chunkSize(): int
    {
        return 500;
    }
}
