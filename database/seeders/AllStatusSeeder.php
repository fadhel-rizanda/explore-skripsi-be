<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
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
            ['status_name' => 'available', 'status_type' => 'pet'],
            ['status_name' => 'adopted', 'status_type' => 'pet'],
            ['status_name' => 'pending', 'status_type' => 'pet'],
            ['status_name' => 'unavailable', 'status_type' => 'pet'],
        ];

        foreach ($statuses as $status) {
            if (!Status::where('status_name', $status['status_name'])
                ->where('status_type', $status['status_type'])
                ->exists()) {
                Status::create([
                    'id' => Str::uuid(),
                    'status_name' => $status['status_name'],
                    'status_type' => $status['status_type'],
                ]);
            }
        }
    }
}
