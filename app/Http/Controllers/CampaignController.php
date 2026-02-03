<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CampaignController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

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
