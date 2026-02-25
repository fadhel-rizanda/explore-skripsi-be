<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $this->seedFromCsv(
            database_path('data/regencies.csv'),
            'mt_regency',
            ['id', 'province_id', 'name']
        );
    }

    private function seedFromCsv($path, $table, $columns)
    {
        $handle = fopen($path, 'r');

        $data = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($columns, $row);
        }

        fclose($handle);

        DB::table($table)->insert($data);
    }
}
