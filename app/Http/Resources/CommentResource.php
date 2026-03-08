<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'parent_id' => $this->parent_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'replies_count' => $this->whenCounted('replies'),
            'created_by' => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'avatar' => $this->createdBy->attachment?->public_url ?? $this->createdBy->avatar,
                'is_active' => $this->createdBy->is_active,
            ],
        ];
    }
}
