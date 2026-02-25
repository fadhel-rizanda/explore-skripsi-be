<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    use ResponseAPI;
    public function listProvinces(Request $request)
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
