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

            'meet_n_greet' => $this->whenLoaded('latestMeetNGreet', fn () => [
                'id' => $this->latestMeetNGreet?->id,
                'adopter_confirmed' => $this->latestMeetNGreet?->adopter_confirmed,
                'adopter_confirmed_at' => $this->latestMeetNGreet?->adopter_confirmed_at,
                'provider_confirmed' => $this->latestMeetNGreet?->provider_confirmed,
                'provider_confirmed_at' => $this->latestMeetNGreet?->provider_confirmed_at,
                'is_active' => $this->latestMeetNGreet?->is_active,
                'schedule' => $this->latestMeetNGreet->relationLoaded('schedule')
                    ? [
                        'id' => $this->latestMeetNGreet->schedule?->id,
                        'scheduled_time' => $this->latestMeetNGreet->schedule?->scheduled_time,
                        'notes' => $this->latestMeetNGreet->schedule?->notes,
                        'address' => $this->latestMeetNGreet->schedule?->address ? [
                            'id' => $this->latestMeetNGreet->schedule->address->id,
                            'street' => $this->latestMeetNGreet->schedule->address->street,
                            'city' => $this->latestMeetNGreet->schedule->address->city,
                            'state' => $this->latestMeetNGreet->schedule->address->state,
                            'country' => $this->latestMeetNGreet->schedule->address->country,
                            'zip_code' => $this->latestMeetNGreet->schedule->address->zip_code,
                            'notes' => $this->latestMeetNGreet->schedule->address->notes,
                            'link' => $this->latestMeetNGreet->schedule->address->link,
                        ] : null,
                    ]
                    : null,
                'status' => $this->latestMeetNGreet?->status,
            ]),

            'handover' => $this->whenLoaded('latestHandover', fn () => [
                'id' => $this->latestHandover?->id,
                'scheduled_at' => $this->latestHandover?->scheduled_at,
                'status' => $this->latestHandover?->status,
                'adopter_finalized' => $this->latestHandover?->adopter_finalized,
                'provider_finalized' => $this->latestHandover?->provider_finalized,
                'admin_finalized' => $this->latestHandover?->admin_finalized,
                'adopter_finalized_at' => $this->latestHandover?->adopter_finalized_at,
                'provider_finalized_at' => $this->latestHandover?->provider_finalized_at,
                'admin_finalized_at' => $this->latestHandover?->admin_finalized_at,
                'evidence' => $this->latestHandover->relationLoaded('attachments')
                    ? $this->latestHandover->attachments->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_type' => $attachment->file_type,
                        'file_size' => $attachment->file_size,
                        'public_url' => $attachment->public_url,
                    ])
                    : null,
                'meet_n_greet' => $this->latestHandover->relationLoaded('meetNGreet') && $this->latestHandover->meetNGreet
                    ? [
                        'id' => $this->latestHandover->meetNGreet->id,
                        'adopter_confirmed' => $this->latestHandover->meetNGreet->adopter_confirmed,
                        'adopter_confirmed_at' => $this->latestHandover->meetNGreet->adopter_confirmed_at,
                        'provider_confirmed' => $this->latestHandover->meetNGreet->provider_confirmed,
                        'provider_confirmed_at' => $this->latestHandover->meetNGreet->provider_confirmed_at,
                        'is_active' => $this->latestHandover->meetNGreet->is_active,
                        'schedule' => $this->latestHandover->meetNGreet->relationLoaded('schedule')
                            ? [
                                'id' => $this->latestHandover->meetNGreet->schedule?->id,
                                'scheduled_time' => $this->latestHandover->meetNGreet->schedule?->scheduled_time,
                                'notes' => $this->latestHandover->meetNGreet->schedule?->notes,
                                'address' => $this->latestHandover->meetNGreet->schedule?->address ? [
                                    'id' => $this->latestHandover->meetNGreet->schedule?->address?->id,
                                    'street' => $this->latestHandover->meetNGreet->schedule?->address?->street,
                                    'city' => $this->latestHandover->meetNGreet->schedule?->address?->city,
                                    'state' => $this->latestHandover->meetNGreet->schedule?->address?->state,
                                    'country' => $this->latestHandover->meetNGreet->schedule?->address?->country,
                                    'zip_code' => $this->latestHandover->meetNGreet->schedule?->address?->zip_code,
                                    'notes' => $this->latestHandover->meetNGreet->schedule?->address?->notes,
                                    'link' => $this->latestHandover->meetNGreet?->schedule?->address?->link,
                                ] : null,
                            ]
                            : null,
                        'status' => $this->latestHandover->meetNGreet?->status,
                    ]
                    : null,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ];
    }
}
