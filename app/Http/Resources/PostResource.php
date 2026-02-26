<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'image_url' => optional($this->attachment)->public_url,
            'is_active' => (bool) $this->is_active,
            'attachment' => $this->attachment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tags' => $this->tags,
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'is_liked' => $this->is_liked ?? false,
            'created_by' => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
                'avatar' => $this->createdBy->avatar ?? optional($this->createdBy->attachment)->public_url,
                'is_active' => $this->createdBy->is_active,
            ],
        ];
    }
}
