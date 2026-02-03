<?php

namespace App\Http\Controllers\Web;

use App\Enums\CharacterClass;
use App\Enums\CharacterRace;
use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class CharacterWebController extends Controller
{
    public function index()
    {
        $characters = Auth::user()->characters()->orderByDesc('updated_at')->paginate(10);
        return view('characters.index', compact('characters'));
    }

    public function create()
    {
        $races = CharacterRace::cases();
        $classes = CharacterClass::cases();

        return view('characters.create', compact('races', 'classes'));
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

        $validatedData['user_id'] = Auth::id();

        $character = Character::create($validatedData);

        // If there is a pending campaign join in session, we could handle it here, 
        // but for now let's just redirect to index or the campaign join page if needed.
        if ($request->has('campaign_id')) {
            return redirect()->route('campaigns.join.view', ['campaign' => $request->input('campaign_id')]); // Assuming we pass data to join view
        }

        return redirect()->route('characters.index')
            ->with('success', 'Personagem criado com sucesso!');
    }

    public function show(Character $character)
    {
        // Reuse policy for view check
        $this->authorize('view', $character);

        return view('characters.show', compact('character'));
    }

    public function edit(Character $character)
    {
        $this->authorize('update', $character);

        $races = CharacterRace::cases();
        $classes = CharacterClass::cases();

        return view('characters.edit', compact('character', 'races', 'classes'));
    }

    public function update(Request $request, Character $character)
    {
        $this->authorize('update', $character);

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

        $character->update($validatedData);

        return redirect()->route('characters.index')
            ->with('success', 'Personagem atualizado com sucesso!');
    }

    public function destroy(Character $character)
    {
        $this->authorize('delete', $character);

        $character->delete();

        return redirect()->route('characters.index')
            ->with('success', 'Personagem excluído com sucesso!');
    }
}
