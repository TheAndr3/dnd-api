<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'race' => $this->race,
            'class' => $this->class,
            'level' => $this->level,
            'stats' => [
                'strength' => $this->strength,
                'dexterity' => $this->dexterity,
                'constitution' => $this->constitution,
                'intelligence' => $this->intelligence,
                'wisdom' => $this->wisdom,
                'charisma' => $this->charisma,
            ],
            'combat' => [
                'hit_points' => $this->hit_points,
                'armor_class' => $this->armor_class,
                'speed' => $this->speed,
                'initiative' => $this->initiative,
                'mana_points' => $this->mana_points,
            ],
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i'),
        ];
    }
}
