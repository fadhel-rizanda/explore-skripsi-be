<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\GetAllRequest;
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

            $comments = $post->comments()->with([
                'createdBy' => function ($query) {
                    $query->select('id', 'name', 'email', 'avatar')
                        ->with('attachment:id,user_id,public_url');
                },
            ])->latest()->paginate($perPage);

            $comments->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'content' => $item->content,
                    'parent_id' => $item->parent_id,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'created_by' => [
                        'id' => $item->createdBy->id,
                        'name' => $item->createdBy->name,
                        'email' => $item->createdBy->email,
                        'avatar' => $item->createdBy->avatar ?? optional($item->createdBy->attachment)->public_url,
                    ],
                ];
            });

            return $this->sendSuccessPagination('Comments retrieved successfully.', $comments);
        } catch (\Throwable $e) {
            \Log::error('Error fetching comments', ['error' => $e->getMessage()]);

            return $this->sendError('Error fetching comments.');
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
