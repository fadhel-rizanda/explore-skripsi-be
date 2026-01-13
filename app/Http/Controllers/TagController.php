<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\AllTag;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class TagController extends Controller
{
    use ResponseAPI;

    public function listTags(GetAllRequest $request)
    {
        $type = $request->type;
        $search = $request->search;

        if ($search) {
            $tags = AllTag::query()
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when(
                    Uuid::isValid($search),
                    fn ($q) => $q->where('id', $search),
                    fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
                )
                ->orderBy('name', 'asc')
                ->get();

            return $this->sendSuccess('Tags retrieved successfully', $tags);
        }

        $tags = Cache::remember(
            'tags.type_' . ($type ?? 'all'),
            now()->addHours(6),
            function () use ($type) {
                return AllTag::when($type, fn ($q) => $q->where('type', $type))->orderBy('name', 'asc')->get();
            }
        );

        return $this->sendSuccess('Tags retrieved successfully', $tags);
    }
}
