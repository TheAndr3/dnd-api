<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use App\Services\CharacterService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

class CharacterController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CharacterService $characterService
    ) {}

    #[OA\Get(
        path: '/characters',
        summary: 'List user characters',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        return CharacterResource::collection($this->characterService->getUserCharacters($request->user()));
    }

    #[OA\Post(
        path: '/characters',
        summary: 'Create a new character',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'race', 'class', 'level', 'strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma', 'hit_points', 'armor_class', 'speed', 'initiative', 'mana_points'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Thorin Oakenshield'),
                    new OA\Property(property: 'race', type: 'string', enum: ['human', 'elf', 'orc', 'dwarf', 'halfling', 'tiefling', 'gnome', 'draconic'], example: 'dwarf'),
                    new OA\Property(property: 'class', type: 'string', enum: ['barbarian', 'bard', 'cleric', 'druid', 'paladin', 'ranger', 'rogue', 'sorcerer', 'wizard'], example: 'barbarian'),
                    new OA\Property(property: 'level', type: 'integer', minimum: 1, maximum: 20, example: 1),
                    new OA\Property(property: 'strength', type: 'integer', minimum: 1, maximum: 30, example: 16),
                    new OA\Property(property: 'dexterity', type: 'integer', minimum: 1, maximum: 30, example: 12),
                    new OA\Property(property: 'constitution', type: 'integer', minimum: 1, maximum: 30, example: 14),
                    new OA\Property(property: 'intelligence', type: 'integer', minimum: 1, maximum: 30, example: 10),
                    new OA\Property(property: 'wisdom', type: 'integer', minimum: 1, maximum: 30, example: 10),
                    new OA\Property(property: 'charisma', type: 'integer', minimum: 1, maximum: 30, example: 8),
                    new OA\Property(property: 'hit_points', type: 'integer', minimum: 1, example: 12),
                    new OA\Property(property: 'armor_class', type: 'integer', minimum: 0, example: 14),
                    new OA\Property(property: 'speed', type: 'integer', minimum: 0, example: 30),
                    new OA\Property(property: 'initiative', type: 'integer', example: 1),
                    new OA\Property(property: 'mana_points', type: 'integer', minimum: 0, example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Character created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreCharacterRequest $request)
    {
        $result = $this->characterService->createCharacter($request->user(), $request->validated());

        return (new CharacterResource($result['character']))->additional($result['external_data'] ? ['external_data' => $result['external_data']] : []);
    }

    #[OA\Get(
        path: '/characters/{id}',
        summary: 'Get character details',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation'),
            new OA\Response(response: 403, description: 'Unauthorized')
        ]
    )]
    public function show(Character $character)
    {
        $this->authorize('view', $character);

        return new CharacterResource($character);
    }

    #[OA\Put(
        path: '/characters/{id}',
        summary: 'Update character',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'level', type: 'integer', minimum: 1, maximum: 20),
                    new OA\Property(property: 'hit_points', type: 'integer', minimum: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Character updated successfully')
        ]
    )]
    public function update(UpdateCharacterRequest $request, Character $character)
    {
        $this->authorize('update', $character);

        return new CharacterResource($this->characterService->updateCharacter($character, $request->validated()));
    }

    #[OA\Delete(
        path: '/characters/{id}',
        summary: 'Delete character',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Character deleted successfully')
        ]
    )]
    public function destroy(Character $character)
    {
        $this->authorize('delete', $character);

        $this->characterService->deleteCharacter($character);

        return response()->json(['message' => 'Personagem excluído com sucesso']);
    }
}
