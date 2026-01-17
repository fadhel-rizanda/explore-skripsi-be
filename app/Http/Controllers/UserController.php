<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UserBackgroundRequest;
use App\Models\Address;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ResponseAPI;

    public function listUsers(GetAllRequest $request)
    {
        try {
            $perPage = min((int)$request->query('per_page', 15), 100);
            $search = $request->query('search');
            $roleId = $request->query('role_id');
            $sortBy = $request->query('sort_by', 'created_at');

            $protectedSortFields = ['name', 'email', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $protectedSortFields)) {
                $sortBy = 'created_at';
            }

            $users = User::with([
                'attachment:id,public_url',
                'roles:id,name'
            ])
                ->when($search, function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($roleId, function ($q, $roleId) {
                    $q->whereHas('roles', function ($query) use ($roleId) {
                        $query->where('id', $roleId);
                    });
                })
                ->orderBy($sortBy, 'desc')
                ->paginate($perPage);

            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ?? optional($user->attachment)->public_url,
                    'role_name' => $user->roles->first()?->name,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return $this->sendSuccessPagination('Users retrieved successfully.', $users);
        } catch (\Exception $e) {
            \Log::error('Error fetching users: ' . $e->getMessage());
            return $this->sendError('Error fetching users: ' . $e->getMessage());
        }
    }

    public function listUsersAdmin(GetAllRequest $request)
    {
        try {
            $perPage = min((int)$request->query('per_page', 15), 100);
            $search = $request->query('search');
            $roleId = $request->query('role_id');
            $sortBy = $request->query('sort_by', 'created_at');

            $protectedSortFields = ['name', 'email', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $protectedSortFields)) {
                $sortBy = 'created_at';
            }

            $users = User::with([
                'attachment:id,public_url',
                'roles:id,name'
            ])
                ->when($search, function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('email', 'ILIKE', "%{$search}%")
                            ->orWhere('phone', 'ILIKE', "%{$search}%")
                            ->orWhere('id', 'ILIKE', "%{$search}%");
                    });
                })
                ->when($roleId, function ($q, $roleId) {
                    $q->whereHas('roles', function ($query) use ($roleId) {
                        $query->where('id', $roleId);
                    });
                })
                ->orderBy($sortBy, 'desc')
                ->paginate($perPage);

            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar ?? optional($user->attachment)->public_url,
                    'role_name' => $user->roles->first()?->name,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return $this->sendSuccessPagination('Users retrieved successfully.', $users);
        } catch (\Exception $e) {
            \Log::error('Error fetching users: ' . $e->getMessage());
            return $this->sendError('Error fetching users: ' . $e->getMessage());
        }
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
                'roles:id,name'
            ])->findOrFail($id);

            $user = [
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

            return $this->sendSuccess('User details retrieved successfully.', $user);
        } catch (\Exception $e) {
            \Log::error('Error fetching user details: ' . $e->getMessage());
            return $this->sendError('Error fetching user details: ' . $e->getMessage());
        }
    }

    public function updateProfile(UpdateUserRequest $request){
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

            $user->load([
                'address',
                'personalityTags:id,name',
                'petExperienceTags:id,name',
                'petPreferencesTags:id,name',
                'attachment:id,public_url'
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

            if (!password_verify($userPassword, $user->password)) {
                return $this->sendError('Incorrect password provided.');
            }

            $user->delete();

            return $this->sendSuccess('User deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting user: ' . $e->getMessage());
            return $this->sendError('Error deleting user: ' . $e->getMessage());
        }
    }
}
