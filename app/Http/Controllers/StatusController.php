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

        if ($search) {
            $statuses = Status::query()
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when(
                    Uuid::isValid($search),
                    fn ($q) => $q->where('id', $search),
                    fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
                )
                ->orderBy('name', 'asc')
                ->get();

            return $this->sendSuccess('Statuses retrieved successfully', $statuses);
        }

        $statuses = Cache::remember(
            'statuses.type_' . ($type ?? 'all'),
            now()->addHours(6),
            function () use ($type) {
                return Status::when($type, fn ($q) => $q->where('type', $type))->orderBy('name', 'asc')->get();
            }
        );

        return $this->sendSuccess('Statuses retrieved successfully', $statuses);
    }
}
