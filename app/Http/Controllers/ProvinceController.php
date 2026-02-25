<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\Province;
use App\Traits\ResponseAPI;

class ProvinceController extends Controller
{
    use ResponseAPI;

    public function listProvinces(GetAllRequest $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $provinces = Province::query()
            ->orderBy('name')
            ->when($search, function ($query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->simplePaginate(15, ['id', 'name'], 'page', $page);

        return $this->sendSuccessPagination('Provinces retrieved successfully', $provinces);
    }
}
