<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'website' => $this->website,
            'image_url' => optional($this->attachment)->public_url,
            'attachment_id' => $this->attachment_id,
            'address_id' => $this->address_id,
            'tags' => $this->tags->pluck('id'),
            'admins' => $this->admins->pluck('user_id'),
            'created_by_id' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
