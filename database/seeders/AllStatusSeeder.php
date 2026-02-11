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
            // ======================
            // PET STATUSES
            // ======================
            ['name' => 'Available', 'type' => 'pet', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['name' => 'Adopted', 'type' => 'pet', 'color_code' => 'bg-blue-50 text-blue-700 border-blue-200'],
            ['name' => 'Pending', 'type' => 'pet', 'color_code' => 'bg-amber-50 text-amber-700 border-amber-200'],
            ['name' => 'Not Available', 'type' => 'pet', 'color_code' => 'bg-gray-50 text-gray-700 border-gray-200'],

            // ======================
            // REPORT STATUSES
            // ======================
            ['name' => 'Active', 'type' => 'report', 'color_code' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            ['name' => 'Resolved', 'type' => 'report', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['name' => 'Closed', 'type' => 'report', 'color_code' => 'bg-slate-100 text-slate-700 border-slate-300'],
            ['name' => 'In Progress', 'type' => 'report', 'color_code' => 'bg-sky-50 text-sky-700 border-sky-200'],

            // ======================
            // ADOPTION STATUSES
            // ======================
            ['name' => 'Pending', 'type' => 'adoption', 'color_code' => 'bg-amber-50 text-amber-700 border-amber-200'],
            ['name' => 'Need an Action', 'type' => 'adoption', 'color_code' => 'bg-orange-50 text-orange-700 border-orange-200'],
            ['name' => 'In Progress', 'type' => 'adoption', 'color_code' => 'bg-sky-50 text-sky-700 border-sky-200'],
            ['name' => 'Completed', 'type' => 'adoption', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['name' => 'Rejected', 'type' => 'adoption', 'color_code' => 'bg-red-50 text-red-700 border-red-200'],
            ['name' => 'Cancelled', 'type' => 'adoption', 'color_code' => 'bg-rose-50 text-rose-700 border-rose-200'],
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
                    'color_code' => $status['color_code'],
                ];
            }
        }

        if (! empty($statusesToInsert)) {
            Status::insert($statusesToInsert);
        }
    }
}
