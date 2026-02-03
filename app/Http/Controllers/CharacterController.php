<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;

class CharacterController extends Controller
{

    public function index()
    {
        return response()->json(Character::paginate(10));
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:255',
            'race' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'level' => 'required|integer',
            'strength' => 'required|integer',
            'dexterity' => 'required|integer',
            'constitution' => 'required|integer',
            'intelligence' => 'required|integer',
            'wisdom' => 'required|integer',
            'charisma' => 'required|integer',
            'hit_points' => 'required|integer',
            'armor_class' => 'required|integer',
            'speed' => 'required|integer',
            'initiative' => 'required|integer',
            'mana_points' => 'required|integer',
        ]);

        $character = Character::create($validateData);

        return response()->json([
            'message' => 'Character created successfully',
            'data' => $character,
        ], 201);
    }

    public function show(Character $character)
    {
        return response()->json($character);
    }

    public function update(Request $request, Character $character)
    {
        $validateData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'race' => 'sometimes|string|max:255',
            'class' => 'sometimes|string|max:255',
            'level' => 'sometimes|integer',
            'strength' => 'sometimes|integer',
            'dexterity' => 'sometimes|integer',
            'constitution' => 'sometimes|integer',
            'intelligence' => 'sometimes|integer',
            'wisdom' => 'sometimes|integer',
            'charisma' => 'sometimes|integer',
            'hit_points' => 'sometimes|integer',
            'armor_class' => 'sometimes|integer',
            'speed' => 'sometimes|integer',
            'initiative' => 'sometimes|integer',
            'mana_points' => 'sometimes|integer',
        ]);

        $character->update($validateData);

        return response()->json([
            'message' => 'Character updated successfully',
            'data' => $character,
        ]);
    }

    public function destroy(Character $character)
    {
        $character->delete();

        return response()->json([
            'message' => 'Character deleted successfully',
        ]);
    }
}
