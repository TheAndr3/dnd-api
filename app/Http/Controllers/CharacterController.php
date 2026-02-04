<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\DnDService;

class CharacterController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // Authenticated user characters only
        $characters = $request->user()->characters()->latest()->paginate(10);
        return CharacterResource::collection($characters);
    }

    public function store(StoreCharacterRequest $request, DnDService $dndService)
    {
        $validatedData = $request->validated();

        $character = $request->user()->characters()->create($validatedData);

        // Fetch external API data
        $classInfo = $dndService->getClassInfo($character->class->value);

        return (new CharacterResource($character))->additional([
            'external_data' => [
                'class_info' => [
                    'hit_die' => $classInfo['hit_die'] ?? null,
                    'proficiencies' => $classInfo['proficiencies'] ?? [],
                    'saving_throws' => $classInfo['saving_throws'] ?? [],
                ]
            ]
        ]);
    }

    public function show(Character $character)
    {
        $this->authorize('view', $character);
        return new CharacterResource($character);
    }

    public function update(UpdateCharacterRequest $request, Character $character)
    {
        $this->authorize('update', $character);

        $character->update($request->validated());

        return new CharacterResource($character);
    }

    public function destroy(Character $character)
    {
        $this->authorize('delete', $character);

        $character->delete();

        return response()->json(['message' => 'Personagem excluído com sucesso']);
    }
}
