<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mt_province')->upsert([
            ['id' => 1,  'name' => 'Online'],
            ['id' => 31, 'name' => 'DKI JAKARTA'],
            ['id' => 32, 'name' => 'JAWA BARAT'],
            ['id' => 36, 'name' => 'BANTEN'],
        ], ['id'], ['name']);
    }
}
