<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenApi\Attributes as OA;

class CampaignController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    #[OA\Get(
        path: '/api/campaigns',
        summary: 'List user campaigns',
        tags: ['Campaigns'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        $user = Auth::user();

        // Get campaigns where user is GM or has characters enrolled
        $campaigns = Campaign::where('gm_id', $user->id)
            ->orWhereHas('characters', fn($q) => $q->where('user_id', $user->id))
            ->with('master:id,name')
            ->paginate(10);

        return response()->json($campaigns);
    }

    #[OA\Post(
        path: '/api/campaigns',
        summary: 'Create a new campaign',
        tags: ['Campaigns'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Campaign created')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $campaign = Campaign::create([
            'name' => $validated['name'],
            'gm_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Campaign created successfully',
            'data' => $campaign,
        ], 201);
    }

    #[OA\Get(
        path: '/api/campaigns/{id}',
        summary: 'Get campaign details',
        tags: ['Campaigns'],
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
    public function show(Campaign $campaign)
    {
        $user = Auth::user();

        // Check if user has access
        if (!$this->userHasAccess($user, $campaign)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $campaign->load(['master:id,name', 'characters']);

        return response()->json($campaign);
    }

    #[OA\Post(
        path: '/api/campaigns/join',
        summary: 'Join a campaign',
        tags: ['Campaigns'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['invitation_code', 'character_id'],
                properties: [
                    new OA\Property(property: 'invitation_code', type: 'string'),
                    new OA\Property(property: 'character_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Joined successfully'),
            new OA\Response(response: 404, description: 'Invalid code'),
            new OA\Response(response: 403, description: 'Unauthorized character')
        ]
    )]
    public function join(Request $request)
    {
        $validated = $request->validate([
            'invitation_code' => 'required|string',
            'character_id' => 'required|exists:characters,id',
        ]);

        $campaign = Campaign::where('invitation_code', $validated['invitation_code'])->first();

        if (!$campaign) {
            return response()->json(['message' => 'Invalid invitation code'], 404);
        }

        $character = Character::findOrFail($validated['character_id']);

        // Verify ownership
        if (!$character->isOwnedBy(Auth::user())) {
            return response()->json(['message' => 'You can only add your own characters'], 403);
        }

        // Check if already enrolled
        if ($campaign->characters()->where('character_id', $character->id)->exists()) {
            return response()->json(['message' => 'Character already in campaign'], 400);
        }

        $campaign->characters()->attach($character->id);

        return response()->json([
            'message' => 'Successfully joined campaign',
            'campaign' => $campaign->load('characters'),
        ]);
    }

    #[OA\Delete(
        path: '/api/campaigns/{id}/characters/{character_id}',
        summary: 'Remove character from campaign',
        tags: ['Campaigns'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'character_id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Character removed')
        ]
    )]
    public function removeCharacter(Campaign $campaign, Character $character)
    {
        $user = Auth::user();

        // Only GM or character owner can remove
        if (!$campaign->isMaster($user) && !$character->isOwnedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $campaign->characters()->detach($character->id);

        return response()->json(['message' => 'Character removed from campaign']);
    }

    private function userHasAccess($user, Campaign $campaign): bool
    {
        if ($campaign->isMaster($user)) {
            return true;
        }

        return $user->characters()
            ->whereHas('campaigns', fn($q) => $q->where('campaigns.id', $campaign->id))
            ->exists();
    }
}
