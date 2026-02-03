<?php

namespace App\Http\Controllers;

use App\Enums\CharacterClass;
use App\Enums\CharacterRace;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CharacterController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Authenticated: show user's characters
            $characters = $user->characters()->paginate(10);
        } else {
            // Guest: show session characters
            $sessionCharacterIds = session('guest_characters', []);
            $characters = Character::whereIn('id', $sessionCharacterIds)->paginate(10);
        }

        return response()->json($characters);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
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
        ]);

        $user = Auth::user();

        if ($user) {
            $validatedData['user_id'] = $user->id;
        }

        $character = Character::create($validatedData);

        // For guests, store character ID in session
        if (!$user) {
            $sessionCharacters = session('guest_characters', []);
            $sessionCharacters[] = $character->id;
            session(['guest_characters' => $sessionCharacters]);
        }

        return response()->json([
            'message' => 'Character created successfully',
            'data' => $character,
        ], 201);
    }

    public function show(Request $request, Character $character)
    {
        $user = Auth::user();

        // Check authorization
        if (!$this->canViewCharacter($user, $character)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Determine view level
        $viewLevel = $this->getViewLevel($user, $character, $request->query('campaign_id'));

        if ($viewLevel === 'limited') {
            return response()->json([
                'id' => $character->id,
                'name' => $character->name,
                'class' => $character->class,
            ]);
        }

        return response()->json($character);
    }

    public function update(Request $request, Character $character)
    {
        $this->authorize('update', $character);

        $validatedData = $request->validate([
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
        ]);

        $character->update($validatedData);

        return response()->json([
            'message' => 'Character updated successfully',
            'data' => $character,
        ]);
    }

    public function destroy(Character $character)
    {
        $user = Auth::user();

        if (!$user || !Auth::user()->can('delete', $character)) {
            // Check if guest owns this character
            $sessionCharacters = session('guest_characters', []);
            if (!in_array($character->id, $sessionCharacters)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $character->delete();

        return response()->json([
            'message' => 'Character deleted successfully',
        ]);
    }

    private function canViewCharacter(?User $user, Character $character): bool
    {
        // Guest characters are public
        if ($character->user_id === null) {
            return true;
        }

        // Owner can view
        if ($user && $character->isOwnedBy($user)) {
            return true;
        }

        // Session owner can view
        $sessionCharacters = session('guest_characters', []);
        if (in_array($character->id, $sessionCharacters)) {
            return true;
        }

        return $user && $user->can('view', $character);
    }

    private function getViewLevel(?User $user, Character $character, ?int $campaignId): string
    {
        // Owner always gets full view
        if ($character->isOwnedBy($user)) {
            return 'full';
        }

        // Guest character or session owner
        if ($character->user_id === null) {
            return 'full';
        }

        // If in session characters
        $sessionCharacters = session('guest_characters', []);
        if (in_array($character->id, $sessionCharacters)) {
            return 'full';
        }

        if ($campaignId && $user) {
            $campaign = \App\Models\Campaign::find($campaignId);
            if ($campaign && $campaign->isMaster($user)) {
                return 'full';
            }
        }

        return 'limited';
    }
}
