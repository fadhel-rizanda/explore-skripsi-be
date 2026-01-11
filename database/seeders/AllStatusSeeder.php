<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AllStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            // Pet statuses
            ['id' => Str::uuid(), 'status_name' => 'available', 'status_type' => 'pet'],
            ['id' => Str::uuid(), 'status_name' => 'adopted', 'status_type' => 'pet'],
            ['id' => Str::uuid(), 'status_name' => 'pending', 'status_type' => 'pet'],
            ['id' => Str::uuid(), 'status_name' => 'unavailable', 'status_type' => 'pet'],
        ];

        DB::table('mt_all_status')->insert($statuses);
    }
}
