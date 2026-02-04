<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\DnDService;
use OpenApi\Attributes as OA;

class CharacterController extends Controller
{
    use AuthorizesRequests;

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
        // Authenticated user characters only
        $characters = $request->user()->characters()->latest()->paginate(10);
        return CharacterResource::collection($characters);
    }

    #[OA\Post(
        path: '/characters',
        summary: 'Create a new character',
        tags: ['Characters'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'race', 'class'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'race', type: 'string'),
                    new OA\Property(property: 'class', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Character created successfully')
        ]
    )]
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
                    new OA\Property(property: 'level', type: 'integer'),
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

        $character->update($request->validated());

        return new CharacterResource($character);
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

        $character->delete();

        return response()->json(['message' => 'Personagem excluído com sucesso']);
    }
}
