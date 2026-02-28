<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\Province;
use App\Traits\ResponseAPI;

class RegencyController extends Controller
{
    use ResponseAPI;

    public function listRegencies(GetAllRequest $request, Province $province)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $regencies = $province->regencies()
            ->orderBy('name')
            ->when($search, fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"))
            ->where('name', '!=', 'Online')
            ->simplePaginate(15, ['id', 'name', 'province_id'], 'page', $page);

        return $this->sendSuccessPagination('Regencies retrieved successfully', $regencies);
    }
}
