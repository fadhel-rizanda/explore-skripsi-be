<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AllTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Type of Animal tags
            ['id' => Str::uuid(), 'tag_name' => 'Dog', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Cat', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Rabbit', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Hamster', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Bird', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Fish', 'tag_type' => 'type_of_animal'],
            ['id' => Str::uuid(), 'tag_name' => 'Reptile', 'tag_type' => 'type_of_animal'],
            
            // Size tags
            ['id' => Str::uuid(), 'tag_name' => 'Small', 'tag_type' => 'size'],
            ['id' => Str::uuid(), 'tag_name' => 'Medium', 'tag_type' => 'size'],
            ['id' => Str::uuid(), 'tag_name' => 'Large', 'tag_type' => 'size'],
            ['id' => Str::uuid(), 'tag_name' => 'Extra Large', 'tag_type' => 'size'],
            
            // Gender tags
            ['id' => Str::uuid(), 'tag_name' => 'Male', 'tag_type' => 'gender'],
            ['id' => Str::uuid(), 'tag_name' => 'Female', 'tag_type' => 'gender'],
            
            // Physique tags
            ['id' => Str::uuid(), 'tag_name' => 'Slim', 'tag_type' => 'physique'],
            ['id' => Str::uuid(), 'tag_name' => 'Normal', 'tag_type' => 'physique'],
            ['id' => Str::uuid(), 'tag_name' => 'Chubby', 'tag_type' => 'physique'],
            ['id' => Str::uuid(), 'tag_name' => 'Muscular', 'tag_type' => 'physique'],
            ['id' => Str::uuid(), 'tag_name' => 'Long Body', 'tag_type' => 'physique'],
            ['id' => Str::uuid(), 'tag_name' => 'Short Body', 'tag_type' => 'physique'],
            
            // Personality tags
            ['id' => Str::uuid(), 'tag_name' => 'Friendly', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Calm', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Playful', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Active', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Lazy', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Aggressive', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Shy', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Independent', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Affectionate', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Protective', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Curious', 'tag_type' => 'personality'],
            ['id' => Str::uuid(), 'tag_name' => 'Trainable', 'tag_type' => 'personality'],
        ];

        DB::table('mt_all_tag')->insert($tags);
    }
}
