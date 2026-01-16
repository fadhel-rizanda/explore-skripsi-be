<?php

namespace App\Http\Controllers;

use App\Models\AllTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalFilterController extends Controller
{
    public function filters(Request $request): JsonResponse
    {
        // Get types of animal from mt_all_tag where type = 'type_of_animal'
        $types_animal = AllTag::where('type', 'type_of_animal')
            ->get(['id', 'name as type_name'])
            ->toArray();

        // Hardcoded ages
        $ages = [
            ["id" => 1, "key" => "0-1", "label" => "0 - 1 year"],
            ["id" => 2, "key" => "1-5", "label" => "1 - 5 years"],
            ["id" => 3, "key" => "5-9", "label" => "5 - 9 years"],
            ["id" => 4, "key" => "10+", "label" => "10+ years"],
        ];

        // Get personality tags from mt_all_tag where type = 'personality'
        $tags_personality = AllTag::where('type', 'personality')
            ->get(['id', 'name as tag_name'])
            ->toArray();

        return response()->json([
            'types_animal' => $types_animal,
            'ages' => $ages,
            'tags_personality' => $tags_personality,
        ]);
    }
}
