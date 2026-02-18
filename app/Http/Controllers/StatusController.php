<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\Status;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class StatusController extends Controller
{
    use ResponseAPI;

    public function listStatuses(GetAllRequest $request)
    {
        $type = $request->type;
        $search = $request->search;
        $page = $request->page ?? 1;

        $query = Status::query()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name');

        if ($search) {
            $statuses = $query->when(
                Uuid::isValid($search),
                fn ($q) => $q->where('id', $search),
                fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
            )->simplePaginate(15, ['id', 'name', 'type', 'color_code'], 'page', $page);
        } else {
            $cacheKey = 'statuses:type_' . ($type ?? 'all') . '_page_' . ($page);
            $statuses = Cache::remember(
                $cacheKey,
                now()->addHours(6),
                fn () => $query->simplePaginate(15, ['id', 'name', 'type', 'color_code'], 'page', $page),
            );
        }

        return $this->sendSuccessPagination('Statuses retrieved successfully', $statuses);
    }
}
