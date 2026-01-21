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

        $query = Status::query()
            ->select('id', 'name', 'type', 'color')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name');

        if ($search) {
            $statuses = $query->when(
                Uuid::isValid($search),
                fn ($q) => $q->where('id', $search),
                fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
            )->get();
        } else {
            $statuses = Cache::remember(
                'statuses.type_' . ($type ?? 'all'),
                now()->addHours(6),
                fn () => $query->get()
            );
        }

        return $this->sendSuccess('Statuses retrieved successfully', $statuses);
    }
}
