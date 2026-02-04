<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CharacterClass;
use App\Enums\CharacterRace;

class UpdateCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auhorization is handled by policy/controller usually, but for Request we can just return true and rely on Policy
        // or check basic ownership here. Controller handles strict policy check.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'race' => ['sometimes', new Enum(CharacterRace::class)],
            'class' => ['sometimes', new Enum(CharacterClass::class)],
            'level' => 'sometimes|integer|min:1|max:20',
            'strength' => 'sometimes|integer|min:1|max:30',
            'dexterity' => 'sometimes|integer|min:1|max:30',
            'constitution' => 'sometimes|integer|min:1|max:30',
            'intelligence' => 'sometimes|integer|min:1|max:30',
            'wisdom' => 'sometimes|integer|min:1|max:30',
            'charisma' => 'sometimes|integer|min:1|max:30',
            'hit_points' => 'sometimes|integer|min:1',
            'armor_class' => 'sometimes|integer|min:0',
            'speed' => 'sometimes|integer|min:0',
            'initiative' => 'sometimes|integer',
            'mana_points' => 'sometimes|integer|min:0',
        ];
    }
}
