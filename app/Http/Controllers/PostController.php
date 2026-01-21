<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    use ResponseAPI;

    public function listPosts(GetAllRequest $request)
    {
        try {
            $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
            $posts = $this->getPostsQuery($request, $isAdmin);

            return $this->sendSuccessPagination(
                'Post retrieved successfully.',
                $posts
            );
        } catch (\Throwable $e) {
            \Log::error('Error fetching posts', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching posts.');
        }
    }

    public function postDetail(Post $post)
    {
        $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
        if (! $isAdmin && ! $post->is_active) {
            return $this->sendError('Post not found.', 404);
        }

        try {
            $post->load([
                'attachment:id,public_url',
                'createdBy:id,name,email,avatar,is_active',
                'createdBy.attachment:id,public_url',
                'tags:id,name,type',
            ]);

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
            DB::beginTransaction();
            $post = Post::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'attachment_id' => $request->input('attachment_id'),
                'community_id' => $request->input('community_id'),
                'created_by' => auth('api')->user()->id,
            ]);

            if ($request->has('tag_ids')) {
                $post->tags()->sync($request->input('tag_ids'));
            }

            DB::commit();

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
        $isAdmin = auth('api')->user()?->hasRole('admin') ?? false;
        if (! $isAdmin && ! $post->is_active) {
            return $this->sendError('Post not found.', 404);
        }

        try {
            DB::beginTransaction();

            if ($request->filled('attachment_id') && $request->attachment_id !== $post->attachment_id) {
                $oldAttachment = $post->attachment;
                if ($oldAttachment) {
                    $oldAttachment->deleteFromStorage();
                }
            }

            $post->update($request->only([
                'title',
                'content',
                'attachment_id',
            ]));

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

            if ($post->attachment) {
                $post->attachment->deleteFromStorage();
            }

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
        $perPage = min((int) $request->query('per_page', 15), 100);
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $communityId = $request->query('community_id');
        $tagId = $request->query('tag_id');

        $allowedSorts = ['title', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $posts = Post::with([
            'attachment:id,public_url',
            'createdBy:id,name,email,avatar,is_active',
            'createdBy.attachment:id,public_url',
            'tags:id,name,type',
        ])
            ->withCount(['likes', 'comments'])
            ->when(! $isAdmin, fn ($q) => $q->where('is_active', true))
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
            ->when($tagId, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('mt_all_tag.id', $tagId)))
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);

        return PostResource::collection($posts);
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
