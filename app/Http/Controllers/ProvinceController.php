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
        $search = $request->search;
        $page = $request->page ?? 1;

        $query = Province::query()
            ->orderBy('name');

        if ($search) {
            $provinces = $query->where('name', 'ILIKE', "%{$search}%")
                ->simplePaginate(15, ['id', 'name'], 'page', $page);
        } else {
            $provinces = $query->simplePaginate(15, ['id', 'name'], 'page', $page);
        }

        return $this->sendSuccessPagination('Provinces retrieved successfully', $provinces);
    }
}
