<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'invitation_code' => $this->invitation_code,
            'master' => $this->whenLoaded('master', fn() => [
                'id' => $this->master->id,
                'name' => $this->master->name,
            ]),
            'characters' => CharacterResource::collection($this->whenLoaded('characters')),
            'characters_count' => $this->whenCounted('characters'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
