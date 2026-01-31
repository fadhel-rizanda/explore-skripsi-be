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
            ['name' => 'Available', 'type' => 'pet'],
            ['name' => 'Adopted', 'type' => 'pet'],
            ['name' => 'Pending', 'type' => 'pet'],
            ['name' => 'Not Available', 'type' => 'pet'],

            ['name' => 'Active', 'type' => 'report'], // report baru
            ['name' => 'Resolved', 'type' => 'report'], // report sudah ditangani
            ['name' => 'Closed', 'type' => 'report'], // report tidak memenuhi syarat
            ['name' => 'In Progress', 'type' => 'report'],

            ['name' => 'Pending', 'type' => 'adoption'],
            ['name' => 'Need an Action', 'type' => 'adoption'],
            ['name' => 'In Progress', 'type' => 'adoption'],
            ['name' => 'Completed', 'type' => 'adoption'],
            ['name' => 'Rejected', 'type' => 'adoption'],
        ];

        // Fetch all existing status pairs in a single query
        $existingStatuses = Status::where(function ($query) use ($statuses) {
            foreach ($statuses as $status) {
                $query->orWhere(function ($subQuery) use ($status) {
                    $subQuery->where('name', $status['name'])
                        ->where('type', $status['type']);
                });
            }
        })->get(['name', 'type']);

        // Build a set of existing (name, type) combinations
        $existingKeys = $existingStatuses
            ->map(function ($status) {
                return $status->name . '|' . $status->type;
            })
            ->all();

        // Collect all missing statuses to insert in a single batch
        $statusesToInsert = [];
        foreach ($statuses as $status) {
            $key = $status['name'] . '|' . $status['type'];
            if (! in_array($key, $existingKeys, true)) {
                $statusesToInsert[] = [
                    'id' => Str::uuid(),
                    'name' => $status['name'],
                    'type' => $status['type'],
                ];
            }
        }

        if (! empty($statusesToInsert)) {
            Status::insert($statusesToInsert);
        }
    }
}
