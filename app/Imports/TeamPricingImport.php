<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class TeamPricingImport
{
    protected int $teamId;

    protected int $batchSize = 1000;

    /** @var array<string,int> */
    protected array $systemPartMap = [];

    /** @var array<string,bool> */
    protected array $existingTeamParts = [];

    public function __construct(int $teamId)
    {
        $this->teamId = $teamId;
        $this->buildSystemPartMap();
        $this->buildExistingTeamPartsMap();
    }

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
                $row = array_change_key_case($row, CASE_LOWER);

                if (empty(array_filter($row))) {
                    continue;
                }

                $manufacturer = $this->normalize($row['manufacturer'] ?? '');
                $modelNumber = $this->normalize(
                    $row['model number']
                    ?? $row['model_number']
                    ?? ''
                );

                if ($manufacturer === '' || $modelNumber === '') {
                    throw new \Exception('Missing manufacturer or model number');
                }

                $key = "{$manufacturer}|{$modelNumber}";

                if (! isset($this->systemPartMap[$key])) {
                    throw new \Exception('System part not found');
                }

                $systemPartId = $this->systemPartMap[$key];

                // Prevent duplicates
                $teamKey = "{$this->teamId}|{$systemPartId}";
                if (isset($this->existingTeamParts[$teamKey])) {
                    continue;
                }

                $listPrice = DB::table('system_parts')
                    ->where('id', $systemPartId)
                    ->value('list_price');

                if ($listPrice === null) {
                    throw new \Exception('List price not found');
                }

                $multiplier = isset($row['multiplier']) && $row['multiplier'] !== ''
                    ? (float) $row['multiplier']
                    : null;

                $staticPrice = isset($row['static price']) && $row['static price'] !== ''
                    ? (float) $row['static price']
                    : null;

                if ($multiplier === null && $staticPrice === null) {
                    throw new \Exception('Multiplier or static price required');
                }

                $teamPrice = $staticPrice !== null
                    ? $staticPrice
                    : round($listPrice * $multiplier, 2);

                $batch[] = [
                    'team_id' => $this->teamId,
                    'system_part_id' => $systemPartId,
                    'multiplier' => $multiplier,
                    'static_price' => $staticPrice,
                    'team_price' => $teamPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $this->existingTeamParts[$teamKey] = true;

                if (count($batch) >= $this->batchSize) {
                    DB::table('team_parts')->insert($batch);
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
            DB::table('team_parts')->insert($batch);
        }

        return $errors;
    }

    protected function buildSystemPartMap(): void
    {
        DB::table('system_parts')
            ->select('id', 'manufacturer', 'model_number')
            ->orderBy('id')
            ->chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $key = $this->normalize($row->manufacturer)
                        .'|'
                        .$this->normalize($row->model_number);

                    $this->systemPartMap[$key] = $row->id;
                }
            });
    }

    protected function buildExistingTeamPartsMap(): void
    {
        DB::table('team_parts')
            ->where('team_id', $this->teamId)
            ->orderBy('id')
            ->select('team_id', 'system_part_id')
            ->chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $this->existingTeamParts[
                        "{$row->team_id}|{$row->system_part_id}"
                    ] = true;
                }
            });
    }

    protected function normalize(string $value): string
    {
        return strtolower(trim($value));
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
