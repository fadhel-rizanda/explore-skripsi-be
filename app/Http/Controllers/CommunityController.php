<?php

namespace App\Http\Controllers;

use App\Enums\ModelReferenceEnum;
use App\Http\Requests\CreateCommunityRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Models\Address;
use App\Models\Community;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    use ResponseAPI;

    public function listCommunities(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $communities = $this->getCommunitiesQuery($request, $isAdmin);

            return $this->sendSuccessPagination(
                'Communities retrieved successfully.',
                $communities
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching communities', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching communities.');
        }
    }

    public function communityDetail(Community $community)
    {
        try {
            $userId = auth('api')->id();
            $community->load([
                'attachment:id,public_url,filename,mime_type,path',
                'address',
                'tags',
                'createdBy',
            ])->loadCount('members');

            $user = auth('api')->user();
            $isAdmin = $user?->hasRole('admin') ?? false;
            $isCreator = $user && $community->created_by === $user->id;

            if (! $isAdmin && ! $isCreator && ! $community->is_active) {
                return $this->sendError('Community not found.', 404);
            }

            if ($userId) {
                $community->loadExists([
                    'members as is_member' => fn ($q) => $q->where('user_id', $userId),
                ]);
            }

            $data = [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'website' => $community->website,
                'image_url' => $community->attachment?->public_url,
                'address' => $community->address,
                'attachment' => $community->attachment,
                'tags' => $community->tags,
                'members_count' => $community->members_count,
                'is_member' => $community->is_member ?? false,
                'created_at' => $community->created_at,
                'updated_at' => $community->updated_at,
                'created_by' => [
                    'id' => $community->createdBy?->id,
                    'name' => $community->createdBy?->name,
                    'is_active' => $community->createdBy?->is_active,
                ],
            ];

            return $this->sendSuccess('Community details retrieved successfully.', $data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Community not found.', 404);
        } catch (\Throwable $e) {
            \Log::error('Error fetching community details', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching community details.');
        }
    }

    public function createCommunity(CreateCommunityRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth('api')->user();
            if ($request->use_owner_address) {
                $ownerAddress = $user->address;
                if (! $ownerAddress) {
                    DB::rollBack();

                    return $this->sendError('Owner address not found. Please provide an address or update your profile with an address.', 422);
                }
                $address = Address::create($ownerAddress->only([
                    'street', 'province_id', 'regency_id', 'district_id', 'zip_code', 'notes', 'link',
                ]));
            } else {
                $address = Address::create($request->validated()['address']);
            }

            $community = Community::create([
                'name' => $request->name,
                'description' => $request->description,
                'website' => $request->website,
                'attachment_id' => $request->attachment_id,
                'address_id' => $address->id,
                'created_by' => $user->id,
            ]);

            $community->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::COMMUNITY->value,
            );

            if ($request->filled('tag_ids')) {
                $community->tags()->sync($request->tag_ids);
            }

            $community->members()->syncWithoutDetaching($user->id);

            DB::commit();

            return $this->sendSuccess(
                'Community created successfully.',
                new CommunityResource(
                    $community->load(['tags'])
                )
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Error creating community', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error creating community.');
        }
    }

    public function updateCommunity(UpdateCommunityRequest $request, Community $community)
    {
        try {
            DB::beginTransaction();

            if ($request->has('address') && $community->address) {
                $community->address->update($request->input('address'));
            }

            $community->update($request->only([
                'name',
                'description',
                'website',
                'attachment_id',
            ]));

            $community->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::COMMUNITY->value,
            );

            if ($request->filled('tag_ids')) {
                $community->tags()->sync($request->tag_ids);
            }

            DB::commit();

            return $this->sendSuccess(
                'Community updated successfully.',
                new CommunityResource($community->load(['tags']))
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Error updating community', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error updating community.');
        }
    }

    public function deleteCommunity(Community $community)
    {
        try {
            DB::beginTransaction();

            $community->setAttachmentMetadata(
                attachmentId: null,
                modelReference: ModelReferenceEnum::COMMUNITY->value,
            );

            $community->delete();

            DB::commit();

            return $this->sendSuccess('Community deleted successfully.', []);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error deleting community', ['error' => $e->getMessage()]);

            return $this->sendError('Error deleting community.');
        }
    }

    private function getCommunitiesQuery(GetAllRequest $request, bool $isAdmin)
    {
        $userId = auth('api')->id();
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('order_by', 'desc');
        $tagId = $request->query('tag_id');

        $allowedSorts = ['name', 'created_at', 'updated_at', 'members_count'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $communities = Community::query()
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
            ->with([
                'attachment:id,public_url',
                ...($isAdmin ? ['address', 'tags'] : []),
            ])
            ->withMemberStatus($userId)
            ->withCount('members')
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query->where('name', 'ILIKE', "%{$search}%");

                    if ($isAdmin && Str::isUuid($search)) {
                        $query->orWhere('id', $search);
                    }
                });
            })
            ->when(
                $tagId,
                fn ($q) => $q->whereHas('tags', fn ($query) => $query->where('mt_all_tag.id', $tagId))
            )
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        $communities->getCollection()->transform(function ($community) use ($isAdmin) {
            $data = [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'image_url' => optional($community->attachment)->public_url,
                'members_count' => $community->members_count,
                'is_member' => $community->is_member ?? false,
                'is_active' => $community->is_active ?? false,
                'created_at' => $community->created_at,
                'updated_at' => $community->updated_at,
                'tags' => $community->tags,
            ];

            if ($isAdmin) {
                $data['address'] = $community->address;
                $data['website'] = $community->website;
            }

            return $data;
        });

        return $communities;
    }

    public function followCommunity(Community $community)
    {
        try {
            $user = auth('api')->user();

            if ($community->created_by === $user->id) {
                return $this->sendError(
                    'Owner cannot unfollow the community.',
                    403
                );
            }

            $isMember = $community->members()
                ->where('user_id', $user->id)
                ->exists();

            if ($isMember) {
                $community->members()->detach($user->id);

                return $this->sendSuccess('Community unfollowed successfully.');
            }

            $community->members()->attach($user->id);

            return $this->sendSuccess('Community followed successfully.');
        } catch (\Throwable $e) {
            \Log::error('Error follow/unfollow community', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error toggling follow for community.');
        }
    }
}
