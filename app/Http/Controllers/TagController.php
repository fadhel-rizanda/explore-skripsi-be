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

        $query = AllTag::query()
            ->select('id', 'name', 'type', 'color_code')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name');

        if ($search) {
            $tags = $query->when(
                Uuid::isValid($search),
                fn ($q) => $q->where('id', $search),
                fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
            )->get();
        } else {
            $tags = Cache::remember(
                'tags.type_' . ($type ?? 'all'),
                now()->addHours(6),
                fn () => $query->get()
            );
        }

        return $this->sendSuccess('Tags retrieved successfully', $tags);
    }
}
