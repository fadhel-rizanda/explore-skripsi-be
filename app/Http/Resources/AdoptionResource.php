<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdoptionResource extends JsonResource
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
            'is_active' => $this->is_active,

            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'color_code' => $this->status->color_code,
            ]),

            'stage_tag' => $this->whenLoaded('stageTag', fn () => [
                'id' => $this->stageTag?->id,
                'name' => $this->stageTag?->name,
            ]),

            'pet' => $this->whenLoaded('pet', fn () => [
                'id' => $this->pet->id,
                'name' => $this->pet->name,
                'breed' => $this->pet->breed ?? null,
            ]),

            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'email' => $this->provider?->email,
                'avatar' => $this->provider?->avatar
                    ?? optional($this->provider?->attachment)->public_url,
            ]),

            'adopter' => $this->whenLoaded('adopter', fn () => [
                'id' => $this->adopter?->id,
                'name' => $this->adopter?->name,
                'email' => $this->adopter?->email,
                'avatar' => $this->adopter?->avatar
                    ?? optional($this->adopter?->attachment)->public_url,
                'is_active' => $this->adopter?->is_active,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ];
    }
}
