<?php

namespace App\Http\Controllers;

use App\Enums\ModelReferenceEnum;
use App\Events\CommunityUpdated;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Http\Services\NotificationService;
use App\Models\Post;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function listPosts(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $paginator = $this->getPostsQuery($request, $isAdmin);
            $posts = PostResource::collection($paginator->items());

            return $this->sendSuccessPagination(
                'Post retrieved successfully.',
                $paginator,
                $posts
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching posts', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching posts.');
        }
    }

    public function postDetail(Post $post)
    {
        try {
            $post->load([
                'attachment',
                'createdBy:id,name,email,avatar,is_active',
                'createdBy.attachment:id,public_url',
                'tags:id,name,type,color_code',
            ])->loadCount(['likes', 'comments']);

            $user = auth('api')->user();
            $isAdmin = $user?->hasRole('admin') ?? false;
            $isCreator = $user && $post->created_by === $user->id;

            if (! $isAdmin && ! $isCreator) {
                if (! $post->is_active) {
                    return $this->sendError('Post not found.', 404);
                }
                if (is_null($post->community_id)) {
                    if (! ($post->createdBy?->is_active ?? true)) {
                        return $this->sendError('Post not found.', 404);
                    }
                } elseif (! ($post->community?->is_active ?? true)) {
                    return $this->sendError('Post not found.', 404);
                }
            }

            if ($userId = auth('api')->id()) {
                $post->loadExists([
                    'likes as is_liked' => fn ($q) => $q->where('user_id', $userId),
                ]);
            }

            return $this->sendSuccess('Post details retrieved successfully.', new PostResource($post));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Post not found.', 404);
        } catch (\Throwable $e) {
            \Log::error('Error fetching post details', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching post details.');
        }
    }

    public function createPost(CreatePostRequest $request)
    {
        try {
            $userId = auth('api')->user()->id;
            DB::beginTransaction();
            $post = Post::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'attachment_id' => $request->input('attachment_id'),
                'community_id' => $request->input('community_id'),
                'created_by' => $userId,
            ]);

            $post->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::POST->value,
            );

            if ($request->has('tag_ids')) {
                $post->tags()->sync($request->input('tag_ids'));
            }

            DB::commit();

            $usersToNotify = $post->community_id
                ? $post->community->members()
                    ->where('user_id', '!=', $userId)
                    ->pluck('user_id')
                    ->toArray()
                : [];

            if (! empty($usersToNotify)) {
                $notification = $this->notificationService->createBulk(
                    userIds: $usersToNotify,
                    title: 'New Post: ' . $post->title,
                    message: 'A new post has been created. Check it out!',
                    referenceType: ModelReferenceEnum::POST->value,
                    referenceId: $post->id,
                )->getNotifications()->first();

                broadcast(new CommunityUpdated($notification, $post->community_id));
            }

            $data = $this->getDataResponse($post);

            return $this->sendSuccess('Post created successfully.', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creating post', ['error' => $e->getMessage()]);

            return $this->sendError('Error creating post.');
        }
    }

    public function updatePost(UpdatePostRequest $request, Post $post)
    {
        try {
            DB::beginTransaction();

            $post->update($request->only([
                'title',
                'content',
                'attachment_id',
            ]));

            $post->setAttachmentMetadata(
                attachmentId: $request->input('attachment_id'),
                modelReference: ModelReferenceEnum::POST->value,
            );

            if ($request->has('tag_ids')) {
                $post->tags()->sync($request->input('tag_ids'));
            }

            DB::commit();

            $data = $this->getDataResponse($post);

            return $this->sendSuccess('Post updated successfully.', $data);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error updating post', ['error' => $e->getMessage()]);

            return $this->sendError('Error updating post.');
        }
    }

    public function deletePost(Post $post)
    {
        try {
            DB::beginTransaction();

            $post->setAttachmentMetadata(
                attachmentId: null,
                modelReference: ModelReferenceEnum::POST->value,
            );

            $post->delete();

            DB::commit();

            return $this->sendSuccess('Post deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error deleting post', ['error' => $e->getMessage()]);

            return $this->sendError('Error deleting post.');
        }
    }

    public function likePost(Post $post)
    {
        try {
            $user = auth('api')->user();
            $result = $post->likes()->toggle($user->id);
            $message = count($result['attached']) > 0 ? 'Post liked successfully.' : 'Post unliked successfully.';

            return $this->sendSuccess($message);
        } catch (\Throwable $e) {
            \Log::error('Error liking/unliking post', ['error' => $e->getMessage()]);

            return $this->sendError('Error liking/unliking post.');
        }
    }

    private function getPostsQuery(GetAllRequest $request, bool $isAdmin)
    {
        $userId = auth('api')->id();
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $communityId = $request->query('community_id');
        $tagId = $request->query('tag_id');
        $orderBy = $request->query('sort_direction') ?? $request->query('order_by', 'desc');

        $allowedSorts = ['title', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $posts = Post::with([
            'attachment:id,public_url',
            'createdBy:id,name,email,avatar,is_active',
            'createdBy.attachment:id,public_url',
            'tags:id,name,type,color_code',
        ])
            ->withCount(['likes', 'comments'])
            ->withLikeStatus($userId)
            ->when($search, function ($q) use ($search, $isAdmin) {
                $q->where(function ($query) use ($search, $isAdmin) {
                    $query->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('content', 'ILIKE', "%{$search}%");
                    if ($isAdmin && Str::isUuid($search)) {
                        $query->orWhere('id', $search);
                    }
                });
            })
            ->when($communityId, fn ($q) => $q->where('community_id', $communityId))
            ->when(! $isAdmin, function ($q) use ($communityId) {
                $q->where('is_active', true);
                if (! $communityId) {
                    $q->whereNull('community_id')
                        ->whereHas('createdBy', fn ($u) => $u->where('is_active', true));
                } else {
                    $q->whereHas('community', fn ($c) => $c->where('is_active', true));
                }
            })
            ->when($tagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('mt_all_tag.id', $tagId)))
            ->orderBy($sortBy, $orderBy)
            ->paginate($perPage);

        return $posts;
    }

    private function getDataResponse(Post $post)
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'image_url' => optional($post->attachment)->public_url,
            'attachment_id' => $post->attachment_id,
            'community_id' => $post->community_id,
            'tags' => $post->tags->pluck('id'),
            'created_by_id' => $post->created_by,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];
    }
}
