<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromCsv(
            database_path('data/districts.csv'),
            'mt_district',
            ['id', 'regency_id', 'name']
        );
    }

    private function seedFromCsv($path, $table, $columns)
    {
        $handle = fopen($path, 'r');

        $chunk = [];
        $chunkSize = 1000;

        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = array_combine($columns, $row);

            if (count($chunk) === $chunkSize) {
                DB::table($table)->insert($chunk);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            DB::table($table)->insert($chunk);
        }

        fclose($handle);
    }
}
