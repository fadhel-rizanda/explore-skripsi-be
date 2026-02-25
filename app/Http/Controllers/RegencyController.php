<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class RegencyController extends Controller
{
    use ResponseAPI;

    public function listRegencies(Request $request)
    {
        $provinceId = $request->query('province_id');
        $search = $request->query('search');
        $page = $request->query('page', 1);

        $query = Regency::query()
            ->when($provinceId, fn ($q) => $q->where('province_id', $provinceId))
            ->orderBy('name');

        if ($search) {
            $regencies = $query->where('name', 'ILIKE', "%{$search}%")
                ->simplePaginate(15, ['id', 'name', 'province_id'], 'page', $page);
        } else {
            $regencies = $query->simplePaginate(15, ['id', 'name', 'province_id'], 'page', $page);
        }

        return $this->sendSuccessPagination('Regencies retrieved successfully', $regencies);
    }
}
