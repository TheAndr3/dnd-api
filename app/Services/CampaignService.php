<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use App\Exceptions\InvalidInvitationCodeException;
use App\Exceptions\CharacterOwnershipException;
use App\Exceptions\CharacterAlreadyInCampaignException;

class CampaignService
{
    /**
     * Get campaigns where user is GM or has enrolled characters.
     */
    public function getUserCampaigns(User $user)
    {
        return Campaign::where('gm_id', $user->id)
            ->orWhereHas('characters', fn($q) => $q->where('user_id', $user->id))
            ->with('master:id,name')
            ->paginate(10);
    }

    /**
     * Create a new campaign with the user as GM.
     */
    public function createCampaign(User $user, array $data): Campaign
    {
        return Campaign::create([
            'name' => $data['name'],
            'gm_id' => $user->id,
        ]);
    }

    /**
     * Load campaign details with master and characters.
     */
    public function getCampaignDetails(Campaign $campaign): Campaign
    {
        return $campaign->load(['master:id,name', 'characters']);
    }

    /**
     * Join a campaign using invitation code and character.
     *
     * @throws InvalidInvitationCodeException
     * @throws CharacterOwnershipException
     * @throws CharacterAlreadyInCampaignException
     */
    public function joinCampaign(User $user, string $invitationCode, int $characterId): Campaign
    {
        $campaign = Campaign::where('invitation_code', $invitationCode)->first();

        if (!$campaign) {
            throw new InvalidInvitationCodeException();
        }

        $character = Character::findOrFail($characterId);

        if (!$character->isOwnedBy($user)) {
            throw new CharacterOwnershipException();
        }

        if ($campaign->characters()->where('character_id', $character->id)->exists()) {
            throw new CharacterAlreadyInCampaignException();
        }

        $campaign->characters()->attach($character->id);

        return $campaign->load('characters');
    }

    /**
     * Remove a character from a campaign.
     *
     * @throws CharacterNotInCampaignException
     */
    public function removeCharacter(Campaign $campaign, Character $character): void
    {
        if (!$campaign->characters()->where('character_id', $character->id)->exists()) {
            throw new \App\Exceptions\CharacterNotInCampaignException();
        }

        $campaign->characters()->detach($character->id);
    }

    /**
     * Check if user has access to a campaign.
     */
    public function userHasAccess(User $user, Campaign $campaign): bool
    {
        if ($campaign->isMaster($user)) {
            return true;
        }

        return $user->characters()
            ->whereHas('campaigns', fn($q) => $q->where('campaigns.id', $campaign->id))
            ->exists();
    }
}
