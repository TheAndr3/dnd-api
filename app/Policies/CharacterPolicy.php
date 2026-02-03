<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    /**
     * Determine if the user can view the character.
     * Returns 'full' for owners/masters, 'limited' for same-campaign players, null for deny.
     */
    public function view(?User $user, Character $character, ?Campaign $campaign = null): bool
    {
        // Owner can always view
        if ($user && $character->isOwnedBy($user)) {
            return true;
        }
        // If viewing in campaign context
        if ($campaign && $user) {
            // Master can view all characters in their campaign
            if ($campaign->isMaster($user)) {
                return true;
            }
            // Player in same campaign can view (limited data handled in controller/resource)
            $userCharacterInCampaign = $user->characters()
                ->whereHas('campaigns', fn($q) => $q->where('campaigns.id', '=', $campaign->id))
                ->exists();

            $characterInCampaign = $character->campaigns()->where('campaigns.id', '=', $campaign->id)->exists();

            if ($userCharacterInCampaign && $characterInCampaign) {
                return true;
            }
        }
        // Guest characters (no user_id) are public
        if ($character->user_id === null) {
            return true;
        }
        return false;
    }
    /**
     * Determine if the user can update the character.
     */
    public function update(?User $user, Character $character): bool
    {
        if ($user === null) {
            return false;
        }
        // Owner can update
        if ($character->isOwnedBy($user)) {
            return true;
        }
        // Master of any campaign where this character is enrolled can update
        $masterCampaignIds = $user->masteredCampaigns()->pluck('id');

        return $character->campaigns()
            ->whereIn('campaigns.id', $masterCampaignIds)
            ->exists();
    }
    /**
     * Determine if the user can delete the character.
     */
    public function delete(?User $user, Character $character): bool
    {
        // Only owner can delete
        return $user && $character->isOwnedBy($user);
    }
}
