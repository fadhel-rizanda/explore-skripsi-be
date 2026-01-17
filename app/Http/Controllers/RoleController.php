<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllRequest;
use App\Models\Role;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class RoleController extends Controller
{
    use ResponseAPI;

    public function listRoles(GetAllRequest $request)
    {
        $search = $request->search;
        $query = Role::query()
            ->select('id', 'name')
            ->orderBy('name');

        if ($search) {
            $roles = $query->when(
                Uuid::isValid($search),
                fn ($q) => $q->where('id', $search),
                fn ($q) => $q->where('name', 'ILIKE', "%{$search}%")
            )->get();
        } else {
            $roles = Cache::remember(
                'roles',
                now()->addHours(6),
                fn () => $query->get()
            );
        }

        return $this->sendSuccess('Roles retrieved successfully', $roles);
    }
}
