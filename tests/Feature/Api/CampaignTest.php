<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_campaign()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/campaigns', [
            'name' => 'Curse of Strahd',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Curse of Strahd');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Curse of Strahd',
            'gm_id' => $user->id,
        ]);
    }

    public function test_user_can_join_campaign_with_valid_code()
    {
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['gm_id' => $gm->id]);

        $player = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $player->id]);

        $response = $this->actingAs($player, 'sanctum')->postJson('/api/campaigns/join', [
            'invitation_code' => $campaign->invitation_code,
            'character_id' => $character->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Successfully joined campaign');

        $this->assertDatabaseHas('campaign_character', [
            'campaign_id' => $campaign->id,
            'character_id' => $character->id,
        ]);
    }

    public function test_user_cannot_join_campaign_with_invalid_code()
    {
        /** @var User $player */
        $player = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $player->id]);

        $response = $this->actingAs($player, 'sanctum')->postJson('/api/campaigns/join', [
            'invitation_code' => 'INVALID123',
            'character_id' => $character->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Invalid invitation code');
    }

    public function test_user_cannot_join_campaign_with_character_they_do_not_own()
    {
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['gm_id' => $gm->id]);

        $otherUser = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $otherUser->id]);

        /** @var User $thiefPlayer */
        $thiefPlayer = User::factory()->create();

        $response = $this->actingAs($thiefPlayer, 'sanctum')->postJson('/api/campaigns/join', [
            'invitation_code' => $campaign->invitation_code,
            'character_id' => $character->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You can only add your own characters');
    }

    public function test_gm_can_edit_character_in_campaign()
    {
        /** @var User $gm */
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['gm_id' => $gm->id]);

        $player = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $player->id]);
        $campaign->characters()->attach($character);

        $response = $this->actingAs($gm, 'sanctum')->putJson("/api/characters/{$character->id}", [
            'name' => 'Name Edited by GM'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Name Edited by GM');

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'name' => 'Name Edited by GM']);
    }

    public function test_player_cannot_edit_other_character_in_campaign()
    {
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['gm_id' => $gm->id]);

        $player1 = User::factory()->create();
        $character1 = Character::factory()->create(['user_id' => $player1->id]);
        $campaign->characters()->attach($character1);

        $player2 = User::factory()->create();
        $character2 = Character::factory()->create(['user_id' => $player2->id]);
        $campaign->characters()->attach($character2);

        // Player 2 tries to edit Player 1's character
        /** @var User $player2 */
        $response = $this->actingAs($player2, 'sanctum')->putJson("/api/characters/{$character1->id}", [
            'name' => 'Hacked Name'
        ]);

        $response->assertStatus(403);
    }

    public function test_gm_can_remove_character_from_campaign()
    {
        /** @var User $gm */
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['gm_id' => $gm->id]);

        $player = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $player->id]);
        $campaign->characters()->attach($character);

        $response = $this->actingAs($gm, 'sanctum')->deleteJson("/api/campaigns/{$campaign->id}/characters/{$character->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('campaign_character', [
            'campaign_id' => $campaign->id,
            'character_id' => $character->id,
        ]);
    }
}
