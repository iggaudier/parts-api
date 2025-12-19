<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class SystemPartsImport
{
    protected int $batchSize = 1000;

    public function import(string $path): array
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setDelimiter($this->detectDelimiter($path));
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        $batch = [];
        $errors = [];

        foreach ($records as $index => $row) {
            try {
                // Normalize keys
                $row = array_change_key_case($row, CASE_LOWER);

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $manufacturer = trim($row['manufacturer'] ?? '');
                $modelNumber = trim($row['model number'] ?? $row['model_number'] ?? '');
                $partType = trim($row['part type'] ?? $row['part_type'] ?? '');
                $priceRaw = trim($row['list price'] ?? $row['list_price'] ?? '');

                if ($manufacturer === '') {
                    throw new \Exception('Missing manufacturer');
                }
                if ($modelNumber === '') {
                    throw new \Exception('Missing model_number');
                }
                if ($partType === '') {
                    throw new \Exception('Missing part_type');
                }

                $price = preg_replace('/[^\d.]/', '', $priceRaw);
                if ($price === '' || ! is_numeric($price)) {
                    throw new \Exception('Invalid list_price');
                }

                $activeRaw = strtoupper(trim($row['active'] ?? 'Y'));
                $isActive = in_array($activeRaw, ['Y', 'YES', '1', 'TRUE'], true);

                $batch[] = [
                    'manufacturer' => $manufacturer,
                    'model_number' => $modelNumber,
                    'part_type' => $partType,
                    'list_price' => (float) $price,
                    'is_active' => $isActive,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $this->batchSize) {
                    $this->insert($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ];
            }
        }

        if (! empty($batch)) {
            $this->insert($batch);
        }

        return $errors;
    }

    protected function insert(array $rows): void
    {
        DB::table('system_parts')->insertOrIgnore($rows);
    }

    protected function detectDelimiter(string $path): string
    {
        $line = fgets(fopen($path, 'r')) ?: '';

        foreach ([',', ';', "\t", '|'] as $delimiter) {
            if (substr_count($line, $delimiter) > 1) {
                return $delimiter;
            }
        }

        return ',';
    }
}
