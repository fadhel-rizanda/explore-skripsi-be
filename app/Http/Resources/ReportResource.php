<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'is_target_active' => (bool) optional($this->reference)->is_active,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'tags' => $this->tags,
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
