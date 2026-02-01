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
            // Type of Animal tags
            ['name' => 'Dog', 'type' => 'type_of_animal'],
            ['name' => 'Cat', 'type' => 'type_of_animal'],
            ['name' => 'Rabbit', 'type' => 'type_of_animal'],
            ['name' => 'Hamster', 'type' => 'type_of_animal'],
            ['name' => 'Bird', 'type' => 'type_of_animal'],
            ['name' => 'Fish', 'type' => 'type_of_animal'],
            ['name' => 'Reptile', 'type' => 'type_of_animal'],

            // Physique tags
            ['name' => 'Slim', 'type' => 'physique'],
            ['name' => 'Normal', 'type' => 'physique'],
            ['name' => 'Chubby', 'type' => 'physique'],
            ['name' => 'Muscular', 'type' => 'physique'],
            ['name' => 'Long Body', 'type' => 'physique'],
            ['name' => 'Short Body', 'type' => 'physique'],

            // Personality tags
            ['name' => 'Friendly', 'type' => 'personality'],
            ['name' => 'Calm', 'type' => 'personality'],
            ['name' => 'Playful', 'type' => 'personality'],
            ['name' => 'Active', 'type' => 'personality'],
            ['name' => 'Lazy', 'type' => 'personality'],
            ['name' => 'Aggressive', 'type' => 'personality'],
            ['name' => 'Shy', 'type' => 'personality'],
            ['name' => 'Independent', 'type' => 'personality'],
            ['name' => 'Affectionate', 'type' => 'personality'],
            ['name' => 'Protective', 'type' => 'personality'],
            ['name' => 'Curious', 'type' => 'personality'],
            ['name' => 'Trainable', 'type' => 'personality'],

            ['name' => 'Submitted', 'type' => 'adoption.stage'],
            ['name' => 'Requirement', 'type' => 'adoption.stage'],
            ['name' => 'Meet & Greet', 'type' => 'adoption.stage'],
            ['name' => 'Handover', 'type' => 'adoption.stage'],
            ['name' => 'Completed', 'type' => 'adoption.stage'],
            ['name' => 'Rejected', 'type' => 'adoption.stage'],
            ['name' => 'Cancelled', 'type' => 'adoption.stage'],
        ];

        foreach ($tags as $tag) {
            AllTag::firstOrCreate($tag);
        }
    }
}
