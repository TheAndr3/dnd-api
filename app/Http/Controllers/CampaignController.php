<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\JoinCampaignRequest;
use App\Models\Campaign;
use App\Models\Character;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use OpenApi\Attributes as OA;

class CampaignController extends Controller implements HasMiddleware
{
    public function __construct(
        private CampaignService $campaignService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    #[OA\Get(
        path: '/campaigns',
        summary: 'List user campaigns',
        tags: ['Campaigns'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return response()->json($this->campaignService->getUserCampaigns(Auth::user()));
    }

    #[OA\Post(
        path: '/campaigns',
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
    public function store(StoreCampaignRequest $request)
    {
        $campaign = $this->campaignService->createCampaign(Auth::user(), $request->validated());

        return response()->json(['message' => 'Campaign created successfully', 'data' => $campaign], 201);
    }

    #[OA\Get(
        path: '/campaigns/{id}',
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
        if (!$this->campaignService->userHasAccess(Auth::user(), $campaign)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->campaignService->getCampaignDetails($campaign));
    }

    #[OA\Post(
        path: '/campaigns/join',
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
    public function join(JoinCampaignRequest $request)
    {
        $campaign = $this->campaignService->joinCampaign(
            Auth::user(),
            $request->invitation_code,
            $request->character_id
        );

        return response()->json(['message' => 'Successfully joined campaign', 'campaign' => $campaign]);
    }

    #[OA\Delete(
        path: '/campaigns/{id}/characters/{character_id}',
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

        if (!$campaign->isMaster($user) && !$character->isOwnedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->campaignService->removeCharacter($campaign, $character);

        return response()->json(['message' => 'Character removed from campaign']);
    }
}
