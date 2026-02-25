<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\Regency;
use App\Traits\ResponseAPI;

class DistrictController extends Controller
{
    use ResponseAPI;

    public function listDistricts(GetAllRequest $request, Regency $regency)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $districts = $regency->districts()
            ->orderBy('name')
            ->when($search, fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"))
            ->simplePaginate(15, ['id', 'name', 'regency_id'], 'page', $page);

        return $this->sendSuccessPagination('Districts retrieved successfully', $districts);
    }
}
