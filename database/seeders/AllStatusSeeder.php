<?php

namespace Database\Seeders;

use App\Models\Status;
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
            ['status_name' => 'available', 'status_type' => 'pet'],
            ['status_name' => 'adopted', 'status_type' => 'pet'],
            ['status_name' => 'pending', 'status_type' => 'pet'],
            ['status_name' => 'unavailable', 'status_type' => 'pet'],
        ];

        // Fetch all existing status pairs in a single query
        $existingStatuses = Status::where(function ($query) use ($statuses) {
            foreach ($statuses as $status) {
                $query->orWhere(function ($subQuery) use ($status) {
                    $subQuery->where('status_name', $status['status_name'])
                        ->where('status_type', $status['status_type']);
                });
            }
        })->get(['status_name', 'status_type']);

        // Build a set of existing (status_name, status_type) combinations
        $existingKeys = $existingStatuses
            ->map(function ($status) {
                return $status->status_name . '|' . $status->status_type;
            })
            ->all();

        // Collect all missing statuses to insert in a single batch
        $statusesToInsert = [];
        foreach ($statuses as $status) {
            $key = $status['status_name'] . '|' . $status['status_type'];
            if (!in_array($key, $existingKeys, true)) {
                $statusesToInsert[] = [
                    'id' => Str::uuid(),
                    'status_name' => $status['status_name'],
                    'status_type' => $status['status_type'],
                ];
            }
        }

        if (!empty($statusesToInsert)) {
            Status::insert($statusesToInsert);
        }
    }
}