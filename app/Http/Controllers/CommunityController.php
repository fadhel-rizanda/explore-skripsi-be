<?php

namespace App\Http\Controllers;

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
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            if (! $isAdmin && ! $community->is_active) {
                return $this->sendError('Community not found.', 404);
            }

            $community->load([
                'attachment:id,public_url',
                'address',
                'tags',
            ])->loadCount('members');

            $data = [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'website' => $community->website,
                'image_url' => $community->attachment?->public_url,
                'address' => $community->address,
                'tags' => $community->tags,
                'members_count' => $community->members_count,
                'created_at' => $community->created_at,
                'updated_at' => $community->updated_at,
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
            $address = Address::create(
                $request->input('address')
            );

            $community = Community::create([
                'name' => $request->name,
                'description' => $request->description,
                'website' => $request->website,
                'attachment_id' => $request->attachment_id,
                'address_id' => $address->id,
                'created_by' => auth('api')->id(),
            ]);

            if ($request->has('tag_ids')) {
                $community->tags()->sync($request->tag_ids);
            }
            if ($request->has('admin_ids')) {
                $community->admins()->sync($request->admin_ids);
            }

            DB::commit();

            return $this->sendSuccess('Community created successfully.', new CommunityResource($community->load('tags', 'admins')));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creating community', ['error' => $e->getMessage()]);

            return $this->sendError('Error creating community.');
        }
    }

    public function updateCommunity(UpdateCommunityRequest $request, Community $community)
    {
        $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
        if (! $isAdmin && ! $community->is_active) {
            return $this->sendError('Community not found.', 404);
        }

        try {
            DB::beginTransaction();

            if ($request->has('address') && $community->address) {
                $community->address->update($request->input('address'));
            }

            if ($request->filled('attachment_id') && $request->attachment_id !== $community->attachment_id) {
                $oldAttachment = $community->attachment;
                if ($oldAttachment->attachment) {
                    $oldAttachment->attachment->deleteFromStorage();
                }
            }

            $community->update($request->only([
                'name',
                'description',
                'website',
                'attachment_id',
            ]));

            if ($request->has('tag_ids')) {
                $community->tags()->sync($request->tag_ids);
            }
            if ($request->has('admin_ids')) {
                $community->admins()->sync($request->admin_ids);
            }

            DB::commit();

            return $this->sendSuccess('Community updated successfully.', new CommunityResource($community->load('tags', 'admins')));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error updating community', ['error' => $e->getMessage()]);

            return $this->sendError('Error updating community.');
        }
    }

    public function deleteCommunity(Community $community)
    {
        try {
            DB::beginTransaction();

            if ($community->attachment) {
                $community->attachment->deleteFromStorage();
            }

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
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $tagId = $request->query('tag_id');

        $allowedSorts = ['name', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $communities = Community::query()
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
            ->with([
                'attachment:id,public_url',
                ...($isAdmin ? ['address', 'tags'] : []),
            ])
            ->withCount('members')
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where('name', 'ILIKE', "%{$search}%");

                if ($isAdmin && Str::isUuid($search)) {
                    $q->orWhere('id', $search);
                }
            })
            ->when(
                $tagId,
                fn ($q) => $q->whereHas('tags', fn ($query) => $query->where('mt_all_tag.id', $tagId))
            )
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);

        $communities->getCollection()->transform(function ($community) use ($isAdmin) {
            $data = [
                'id' => $community->id,
                'name' => $community->name,
                'description' => $community->description,
                'image_url' => optional($community->attachment)->public_url,
                'members_count' => $community->members_count,
                'created_at' => $community->created_at,
                'updated_at' => $community->updated_at,
            ];

            if ($isAdmin) {
                $data['address'] = $community->address;
                $data['website'] = $community->website;
                $data['tags'] = $community->tags;
            }

            return $data;
        });

        return $communities;
    }
}
