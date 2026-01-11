<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class AllStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            // Pet statuses
            ['status_name' => 'available', 'status_type' => 'pet'],
            ['status_name' => 'adopted', 'status_type' => 'pet'],
            ['status_name' => 'pending', 'status_type' => 'pet'],
            ['status_name' => 'unavailable', 'status_type' => 'pet'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate($status);
        }
    }
}
