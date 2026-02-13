<?php

namespace Database\Seeders;

use App\Models\AllTag;
use Illuminate\Database\Seeder;

class AllTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [

            // ======================
            // TYPE OF ANIMAL
            // ======================
            ['name' => 'Dog', 'type' => 'type_of_animal', 'color_code' => 'bg-amber-50 text-amber-700 border-amber-200'],
            ['name' => 'Cat', 'type' => 'type_of_animal', 'color_code' => 'bg-orange-50 text-orange-700 border-orange-200'],
            ['name' => 'Rabbit', 'type' => 'type_of_animal', 'color_code' => 'bg-pink-50 text-pink-700 border-pink-200'],
            ['name' => 'Hamster', 'type' => 'type_of_animal', 'color_code' => 'bg-yellow-50 text-yellow-700 border-yellow-200'],
            ['name' => 'Bird', 'type' => 'type_of_animal', 'color_code' => 'bg-sky-50 text-sky-700 border-sky-200'],
            ['name' => 'Fish', 'type' => 'type_of_animal', 'color_code' => 'bg-blue-50 text-blue-700 border-blue-200'],
            ['name' => 'Reptile', 'type' => 'type_of_animal', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],

            // ======================
            // PHYSIQUE
            // ======================
            ['name' => 'Slim', 'type' => 'physique', 'color_code' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            ['name' => 'Normal', 'type' => 'physique', 'color_code' => 'bg-gray-50 text-gray-700 border-gray-200'],
            ['name' => 'Chubby', 'type' => 'physique', 'color_code' => 'bg-rose-50 text-rose-700 border-rose-200'],
            ['name' => 'Muscular', 'type' => 'physique', 'color_code' => 'bg-red-50 text-red-700 border-red-200'],
            ['name' => 'Long Body', 'type' => 'physique', 'color_code' => 'bg-purple-50 text-purple-700 border-purple-200'],
            ['name' => 'Short Body', 'type' => 'physique', 'color_code' => 'bg-cyan-50 text-cyan-700 border-cyan-200'],

            // ======================
            // PERSONALITY
            // ======================
            ['name' => 'Friendly', 'type' => 'personality', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['name' => 'Calm', 'type' => 'personality', 'color_code' => 'bg-blue-50 text-blue-700 border-blue-200'],
            ['name' => 'Playful', 'type' => 'personality', 'color_code' => 'bg-yellow-50 text-yellow-700 border-yellow-200'],
            ['name' => 'Active', 'type' => 'personality', 'color_code' => 'bg-orange-50 text-orange-700 border-orange-200'],
            ['name' => 'Lazy', 'type' => 'personality', 'color_code' => 'bg-slate-100 text-slate-700 border-slate-300'],
            ['name' => 'Aggressive', 'type' => 'personality', 'color_code' => 'bg-red-50 text-red-700 border-red-200'],
            ['name' => 'Shy', 'type' => 'personality', 'color_code' => 'bg-purple-50 text-purple-700 border-purple-200'],
            ['name' => 'Independent', 'type' => 'personality', 'color_code' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            ['name' => 'Affectionate', 'type' => 'personality', 'color_code' => 'bg-pink-50 text-pink-700 border-pink-200'],
            ['name' => 'Protective', 'type' => 'personality', 'color_code' => 'bg-rose-50 text-rose-700 border-rose-200'],
            ['name' => 'Curious', 'type' => 'personality', 'color_code' => 'bg-cyan-50 text-cyan-700 border-cyan-200'],
            ['name' => 'Trainable', 'type' => 'personality', 'color_code' => 'bg-teal-50 text-teal-700 border-teal-200'],

            // ======================
            // ADOPTION STAGE
            // ======================
            ['name' => 'Submitted', 'type' => 'adoption.stage', 'color_code' => 'bg-blue-50 text-blue-700 border-blue-200'],
            ['name' => 'Requirement', 'type' => 'adoption.stage', 'color_code' => 'bg-amber-50 text-amber-700 border-amber-200'],
            ['name' => 'Meet & Greet', 'type' => 'adoption.stage', 'color_code' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            ['name' => 'Handover', 'type' => 'adoption.stage', 'color_code' => 'bg-purple-50 text-purple-700 border-purple-200'],
            ['name' => 'Completed', 'type' => 'adoption.stage', 'color_code' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            ['name' => 'Rejected', 'type' => 'adoption.stage', 'color_code' => 'bg-red-50 text-red-700 border-red-200'],
            ['name' => 'Cancelled', 'type' => 'adoption.stage', 'color_code' => 'bg-rose-50 text-rose-700 border-rose-200'],
        ];

        foreach ($tags as $tag) {
            AllTag::firstOrCreate(
                [
                    'name' => $tag['name'],
                    'type' => $tag['type'],
                ],
                [
                    'color_code' => $tag['color_code'],
                ]
            );
        }
    }
}
