<?php

namespace App\Http\Controllers;

use App\Enums\ModelReferenceEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Address;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ResponseAPI;

    public function listUsers(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $users = $this->getUsersQuery($request, $isAdmin);

            return $this->sendSuccessPagination('Users retrieved successfully.', $users);
        } catch (\Exception $e) {
            \Log::error('Error fetching users: ' . $e->getMessage());

            return $this->sendError('Error fetching users: ' . $e->getMessage());
        }
    }

    private function getUsersQuery(GetAllRequest $request, bool $isAdmin)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $roleId = $request->query('role_id');
        $sortBy = $request->query('sort_by', 'created_at');

        $allowedSorts = $isAdmin
            ? ['name', 'email', 'created_at', 'updated_at']
            : ['name', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $users = User::with([
            'attachment:id,public_url',
            'roles:id,name',
        ])
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query->where('name', 'ILIKE', "%{$search}%");

                    if ($isAdmin) {
                        $query->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('phone', 'ILIKE', "%{$search}%");
                        if (Str::isUuid($search)) {
                            $query->orWhere('id', $search);
                        }
                    }
                });
            })
            ->when(
                $roleId,
                fn ($q, $roleId) => $q->whereHas('roles', fn ($query) => $query->where('id', $roleId))
            )
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);

        $users->getCollection()->transform(function ($user) use ($isAdmin) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? optional($user->attachment)->public_url,
                'role_name' => $user->roles->first()?->name,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            if ($isAdmin) {
                $data['email'] = $user->email;
                $data['phone'] = $user->phone;
            }

            return $data;
        });

        return $users;
    }

    public function userDetails(User $user)
    {
        $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
        if (! $isAdmin && ! $user->is_active) {
            return $this->sendError('User not found.', 404);
        }

        try {
            $user->load([
                'attachment:id,public_url',
                'address',
                'personalityTags:id,name,type,color_code',
                'petExperienceTags:id,name,type,color_code',
                'petPreferencesTags:id,name,type,color_code',
                'roles:id,name',
            ]);

            $userResponse = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'about_me' => $user->about_me,
                'avatar' => $user->avatar ?? optional($user->attachment)->public_url,
                'street' => $user->street,
                'role_name' => $user->roles->first()?->name,
                'personality' => $user->personality,
                'pet_experience' => $user->pet_experience,
                'pet_preferences' => $user->pet_preferences,
                'personality_tags' => $user->personalityTags,
                'pet_experience_tags' => $user->petExperienceTags,
                'pet_preferences_tags' => $user->petPreferencesTags,
                'open_to_special_needs' => $user->open_to_special_needs,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return $this->sendSuccess('User details retrieved successfully.', $userResponse);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('User not found.', 404);
        } catch (\Exception $e) {
            \Log::error('Error fetching user details: ' . $e->getMessage());

            return $this->sendError('Error fetching user details: ' . $e->getMessage());
        }
    }

    public function updateProfile(UpdateUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth('api')->user();

            $user->update($request->only([
                'name',
                'phone',
                'about_me',
                'personality',
                'pet_experience',
                'pet_preferences',
                'open_to_special_needs',
                'attachment_id',
            ]));

            $user->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::USER->value,
            );

            $requiredAddressFields = [
                'street',
                'city',
                'state',
                'zip_code',
                'country',
            ];

            $addressFields = [
                'street',
                'city',
                'state',
                'zip_code',
                'country',
                'notes',
                'link',
            ];

            if ($request->hasAny($addressFields)) {
                if ($user->address) {
                    $user->address->update(
                        $request->only($addressFields)
                    );
                } else {
                    if (! $request->filled($requiredAddressFields)) {
                        return $this->sendError(
                            'To create an address, street, city, state, zip code, and country are required.',
                            422
                        );
                    }
                    $address = Address::create(
                        $request->only($addressFields)
                    );
                    $user->update([
                        'address_id' => $address->id,
                    ]);
                }
            }

            if ($request->has('personality_tags')) {
                $user->personalityTags()->sync($request->input('personality_tags'));
            }
            if ($request->has('pet_experience_tags')) {
                $user->petExperienceTags()->sync($request->input('pet_experience_tags'));
            }
            if ($request->has('pet_preferences_tags')) {
                $user->petPreferencesTags()->sync($request->input('pet_preferences_tags'));
            }

            $user->touch();

            DB::commit();

            $user->load([
                'address',
                'personalityTags:id,name,type,color_code',
                'petExperienceTags:id,name,type,color_code',
                'petPreferencesTags:id,name,type,color_code',
                'attachment:id,public_url',
            ]);

            $user->avatar = $user->avatar ?? optional($user->attachment)->public_url;

            return $this->sendSuccess('User profile updated successfully.', $user);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating user profile: ' . $e->getMessage());

            return $this->sendError('Error updating user profile: ' . $e->getMessage());
        }
    }

    public function deleteUser(DeleteUserRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = auth('api')->user();
            $userPassword = $request->input('password');

            if (! password_verify($userPassword, $user->password)) {
                return $this->sendError('Incorrect password provided.', 422);
            }

            $user->setAttachmentMetadata(
                attachmentId: null,
                modelReference: ModelReferenceEnum::USER->value,
            );

            $user->delete();
            DB::commit();

            return $this->sendSuccess('User deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting user: ' . $e->getMessage());

            return $this->sendError('Error deleting user: ' . $e->getMessage());
        }
    }

    public function userOptions(GetAllRequest $request)
    {
        $search = $request->search;
        $page = $request->page ?? 1;

        $query = User::query()
            ->withoutRole(RoleEnum::ADMIN->value)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $users = $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            })->simplePaginate(15, ['id', 'name'], 'page', $page);
        } else {
            $cacheKey = "users:list:page_{$page}";

            $users = Cache::remember(
                $cacheKey,
                now()->addHours(6),
                fn () => $query->simplePaginate(15, ['id', 'name'], 'page', $page)
            );

        }

        return $this->sendSuccessPagination('Users retrieved successfully', $users);
    }
}
