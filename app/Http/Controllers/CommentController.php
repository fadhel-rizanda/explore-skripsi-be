<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\GetAllRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Traits\ResponseAPI;

class CommentController extends Controller
{
    use ResponseAPI;

    public function listComments(Post $post, GetAllRequest $request)
    {
        try {
            $perPage = min((int) $request->query('per_page', 15), 100);

            $comments = $post->comments()
                ->whereNull('parent_id')
                ->with([
                    'createdBy:id,name,avatar,is_active',
                    'createdBy.attachment:id,user_id,public_url',
                ])
                ->withCount('replies')
                ->latest()
                ->paginate($perPage);

            $comments->getCollection()->transform(fn ($comment) => new CommentResource($comment));

            return $this->sendSuccessPagination('Comments retrieved successfully.', $comments);
        } catch (\Throwable $e) {
            \Log::error('Error fetching comments', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching comments.');
        }
    }

    public function listReplies(Post $post, Comment $comment, GetAllRequest $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $perPage = min((int) $request->query('per_page', 15), 100);

            $replies = $comment->replies()
                ->with([
                    'createdBy:id,name,email,avatar,is_active',
                    'createdBy.attachment:id,user_id,public_url',
                ])
                ->oldest()
                ->simplePaginate($perPage, ['*'], 'page', $page);

            return $this->sendSuccessPagination('Replies retrieved successfully.', $replies);

        } catch (\Throwable $e) {
            \Log::error('Error fetching replies', ['error' => $e->getMessage()]);
            return $this->sendError('Error fetching replies.');
        }
    }

    public function createComment(Post $post, CreateCommentRequest $request)
    {
        try {
            $comment = $post->comments()->create([
                'content' => $request->input('content'),
                'created_by' => auth('api')->id(),
                'parent_id' => $request->input('parent_id', null),
            ]);

            return $this->sendSuccess('Comment created successfully.', $comment);
        } catch (\Throwable $e) {
            \Log::error('Error creating comment', ['error' => $e->getMessage()]);

            return $this->sendError('Error creating comment.');
        }
    }

    public function deleteComment(Post $post, Comment $comment)
    {
        try {
            if ($comment->post_id !== $post->id) {
                return $this->sendError('Comment not found for this post.', 404);
            }
            $comment->delete();

            return $this->sendSuccess('Comment deleted successfully.');
        } catch (\Throwable $e) {
            \Log::error('Error deleting comment', ['error' => $e->getMessage()]);

            return $this->sendError('Error deleting comment.');
        }
    }
}
