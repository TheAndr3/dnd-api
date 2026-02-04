<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\CharacterClass;
use App\Enums\CharacterRace;

class StoreCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'race' => ['required', new Enum(CharacterRace::class)],
            'class' => ['required', new Enum(CharacterClass::class)],
            'level' => 'required|integer|min:1|max:20',
            'strength' => 'required|integer|min:1|max:30',
            'dexterity' => 'required|integer|min:1|max:30',
            'constitution' => 'required|integer|min:1|max:30',
            'intelligence' => 'required|integer|min:1|max:30',
            'wisdom' => 'required|integer|min:1|max:30',
            'charisma' => 'required|integer|min:1|max:30',
            'hit_points' => 'required|integer|min:1',
            'armor_class' => 'required|integer|min:0',
            'speed' => 'required|integer|min:0',
            'initiative' => 'required|integer',
            'mana_points' => 'required|integer|min:0',
        ];
    }
}
