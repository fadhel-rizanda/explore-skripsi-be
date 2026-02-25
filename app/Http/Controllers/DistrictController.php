<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    use ResponseAPI;

    public function listDistricts(Request $request)
    {
        $regencyId = $request->query('regency_id');
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $query = District::query()
            ->when($regencyId, fn ($q) => $q->where('regency_id', $regencyId))
            ->orderBy('name');

        $districts = $query->when($search, fn ($q) => $q->where('name', 'ILIKE', "%{$search}%"))
            ->simplePaginate(15, ['id', 'name', 'regency_id'], 'page', $page);

        return $this->sendSuccessPagination('Districts retrieved successfully', $districts);
    }
}
