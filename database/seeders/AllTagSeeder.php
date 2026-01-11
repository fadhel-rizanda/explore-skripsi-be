<?php

namespace Database\Seeders;

use App\Models\AllTag;
use Illuminate\Database\Seeder;
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
            ['tag_name' => 'Dog', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Cat', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Rabbit', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Hamster', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Bird', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Fish', 'tag_type' => 'type_of_animal'],
            ['tag_name' => 'Reptile', 'tag_type' => 'type_of_animal'],
            
            // Size tags
            ['tag_name' => 'Small', 'tag_type' => 'size'],
            ['tag_name' => 'Medium', 'tag_type' => 'size'],
            ['tag_name' => 'Large', 'tag_type' => 'size'],
            ['tag_name' => 'Extra Large', 'tag_type' => 'size'],
            
            // Gender tags
            ['tag_name' => 'Male', 'tag_type' => 'gender'],
            ['tag_name' => 'Female', 'tag_type' => 'gender'],
            
            // Physique tags
            ['tag_name' => 'Slim', 'tag_type' => 'physique'],
            ['tag_name' => 'Normal', 'tag_type' => 'physique'],
            ['tag_name' => 'Chubby', 'tag_type' => 'physique'],
            ['tag_name' => 'Muscular', 'tag_type' => 'physique'],
            ['tag_name' => 'Long Body', 'tag_type' => 'physique'],
            ['tag_name' => 'Short Body', 'tag_type' => 'physique'],
            
            // Personality tags
            ['tag_name' => 'Friendly', 'tag_type' => 'personality'],
            ['tag_name' => 'Calm', 'tag_type' => 'personality'],
            ['tag_name' => 'Playful', 'tag_type' => 'personality'],
            ['tag_name' => 'Active', 'tag_type' => 'personality'],
            ['tag_name' => 'Lazy', 'tag_type' => 'personality'],
            ['tag_name' => 'Aggressive', 'tag_type' => 'personality'],
            ['tag_name' => 'Shy', 'tag_type' => 'personality'],
            ['tag_name' => 'Independent', 'tag_type' => 'personality'],
            ['tag_name' => 'Affectionate', 'tag_type' => 'personality'],
            ['tag_name' => 'Protective', 'tag_type' => 'personality'],
            ['tag_name' => 'Curious', 'tag_type' => 'personality'],
            ['tag_name' => 'Trainable', 'tag_type' => 'personality'],
        ];

        foreach ($tags as $tag) {
            if (!AllTag::where('tag_name', $tag['tag_name'])
                ->where('tag_type', $tag['tag_type'])
                ->exists()) {
                AllTag::create([
                    'id' => Str::uuid(),
                    'tag_name' => $tag['tag_name'],
                    'tag_type' => $tag['tag_type'],
                ]);
            }
        }
    }
}
