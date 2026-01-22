<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PetMonitorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'animal_id' => $this->id,
            'name' => $this->name,
            'breed' => $this->breed,
            'tag' => $this->whenLoaded('typeOfAnimal', fn() => $this->typeOfAnimal?->name),
            'provider_id' => $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
