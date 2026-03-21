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
        DB::table('mt_regency')->upsert([
            ['id' => 3101, 'province_id' => 31, 'name' => 'KABUPATEN KEPULAUAN SERIBU'],
            ['id' => 3171, 'province_id' => 31, 'name' => 'KOTA JAKARTA SELATAN'],
            ['id' => 3172, 'province_id' => 31, 'name' => 'KOTA JAKARTA TIMUR'],
            ['id' => 3173, 'province_id' => 31, 'name' => 'KOTA JAKARTA PUSAT'],
            ['id' => 3174, 'province_id' => 31, 'name' => 'KOTA JAKARTA BARAT'],
            ['id' => 3175, 'province_id' => 31, 'name' => 'KOTA JAKARTA UTARA'],

            ['id' => 3201, 'province_id' => 32, 'name' => 'KABUPATEN BOGOR'],
            ['id' => 3216, 'province_id' => 32, 'name' => 'KABUPATEN BEKASI'],
            ['id' => 3271, 'province_id' => 32, 'name' => 'KOTA BOGOR'],
            ['id' => 3275, 'province_id' => 32, 'name' => 'KOTA BEKASI'],
            ['id' => 3276, 'province_id' => 32, 'name' => 'KOTA DEPOK'],

            ['id' => 3603, 'province_id' => 36, 'name' => 'KABUPATEN TANGERANG'],
            ['id' => 3671, 'province_id' => 36, 'name' => 'KOTA TANGERANG'],
            ['id' => 3674, 'province_id' => 36, 'name' => 'KOTA TANGERANG SELATAN'],

            ['id' => 1, 'province_id' => 1, 'name' => 'Online'],
        ], ['id'], ['province_id', 'name']);
    }
}
