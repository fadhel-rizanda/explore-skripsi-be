<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Address;
use App\Models\User;
use App\Traits\ResponseAPI;

class UserController extends Controller
{
    use ResponseAPI;

    public function listUsers(GetAllRequest $request)
    {
        try {
            $users = $this->getUsersQuery($request, false);

            return $this->sendSuccessPagination('Users retrieved successfully.', $users);
        } catch (\Exception $e) {
            \Log::error('Error fetching users: ' . $e->getMessage());

            return $this->sendError('Error fetching users: ' . $e->getMessage());
        }
    }

    public function listUsersAdmin(GetAllRequest $request)
    {
        try {
            $users = $this->getUsersQuery($request, true);

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

        // Simple validation
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $users = User::with(['attachment:id,public_url', 'roles:id,name'])
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query->where('name', 'ILIKE', "%{$search}%");

                    if ($isAdmin) {
                        $query->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('phone', 'ILIKE', "%{$search}%")
                            ->orWhere('id', 'ILIKE', "%{$search}%");
                    }
                });
            })
            ->when(
                $roleId,
                fn ($q, $roleId) => $q->whereHas('roles', fn ($query) => $query->where('id', $roleId))
            )
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);

        // Transform inline
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

    public function userDetails($id)
    {
        try {
            $user = User::with([
                'attachment:id,public_url',
                'address',
                'personalityTags:id,name',
                'petExperienceTags:id,name',
                'petPreferencesTags:id,name',
                'roles:id,name',
            ])->findOrFail($id);

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
            $user = auth('api')->user();
            $user->update($request->only([
                'name',
                'email',
                'phone',
                'about_me',
                'personality',
                'pet_experience',
                'pet_preferences',
                'open_to_special_needs',
                'attachment_id',
            ]));

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

            $user->load([
                'address',
                'personalityTags:id,name',
                'petExperienceTags:id,name',
                'petPreferencesTags:id,name',
                'attachment:id,public_url',
            ]);

            $user->avatar = $user->avatar ?? optional($user->attachment)->public_url;

            return $this->sendSuccess('User profile updated successfully.', $user);
        } catch (\Exception $e) {
            \Log::error('Error updating user profile: ' . $e->getMessage());

            return $this->sendError('Error updating user profile: ' . $e->getMessage());
        }
    }

    public function deleteUser(DeleteUserRequest $request)
    {
        try {
            $user = auth('api')->user();
            $userPassword = $request->input('password');

            if (! password_verify($userPassword, $user->password)) {
                return $this->sendError('Incorrect password provided.', 422);
            }

            $user->delete();

            return $this->sendSuccess('User deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());

            return $this->sendError('Error deleting user: ' . $e->getMessage());
        }
    }
}
