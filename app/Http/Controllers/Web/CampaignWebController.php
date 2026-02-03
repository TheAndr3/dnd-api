<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignWebController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $campaigns = Campaign::where('gm_id', $user->id)
            ->orWhereHas('characters', fn($q) => $q->where('user_id', $user->id))
            ->with('master')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
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

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campanha criada com sucesso!');
    }

    public function show(Campaign $campaign)
    {
        $user = Auth::user();

        if (!$this->userHasAccess($user, $campaign)) {
            abort(403, 'Você não tem permissão para acessar esta campanha.');
        }

        $campaign->load(['master', 'characters.owner']);
        // Load characters with their owners to display names

        return view('campaigns.show', compact('campaign'));
    }

    public function join(Request $request)
    {
        $validated = $request->validate([
            'invitation_code' => 'required|string',
        ]);

        $campaign = Campaign::where('invitation_code', $validated['invitation_code'])->first();

        if (!$campaign) {
            return back()->withErrors(['invitation_code' => 'Código de convite inválido.']);
        }

        // Check if already in campaign
        // If master, redirect to show
        if ($campaign->isMaster(Auth::user())) {
            return redirect()->route('campaigns.show', $campaign);
        }

        // If already has a character in this campaign, redirect to show (simplify for now, or maybe show selection again?)
        // Let's redirect to a "join selection" view where they pick the character
        return view('campaigns.join', compact('campaign'));
    }

    public function processJoin(Request $request, Campaign $campaign)
    {
        // This method will handle the actual attachment of character
        $validated = $request->validate([
            'character_id' => 'required|exists:characters,id',
        ]);

        $character = Character::findOrFail($validated['character_id']);

        if (!$character->isOwnedBy(Auth::user())) {
            abort(403, 'Este personagem não pertence a você.');
        }

        if ($campaign->characters()->where('character_id', $character->id)->exists()) {
            return redirect()->route('campaigns.show', $campaign)
                ->with('warning', 'Este personagem já está na campanha.');
        }

        $campaign->characters()->attach($character->id);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Você entrou na campanha com sucesso!');
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
