<?php

namespace Tests\Feature\Api;

use App\Enums\CharacterClass;
use App\Enums\CharacterRace;
use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_characters()
    {
        /** @var User $user */
        $user = User::factory()->create();
        Character::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/characters');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_character()
    {
        /** @var User $user */
        $user = User::factory()->create();

        \Illuminate\Support\Facades\Http::fake([
            'https://www.dnd5eapi.co/api/classes/*' => \Illuminate\Support\Facades\Http::response(['hit_die' => 12], 200),
        ]);

        $data = [
            'name' => 'Conan',
            'race' => CharacterRace::HUMAN->value,
            'class' => CharacterClass::BARBARIAN->value,
            'level' => 1,
            'strength' => 15,
            'dexterity' => 12,
            'constitution' => 14,
            'intelligence' => 8,
            'wisdom' => 10,
            'charisma' => 10,
            'hit_points' => 12,
            'armor_class' => 10,
            'speed' => 30,
            'initiative' => 1,
            'mana_points' => 0,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Conan')
            ->assertJsonPath('external_data.class_info.hit_die', 12);

        $this->assertDatabaseHas('characters', ['name' => 'Conan']);
    }

    public function test_can_view_character()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/characters/{$character->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $character->id);
    }

    public function test_can_update_character()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/characters/{$character->id}", [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('characters', ['id' => $character->id, 'name' => 'Updated Name']);
    }

    public function test_can_delete_character()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/characters/{$character->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('characters', ['id' => $character->id]);
    }

    public function test_cannot_access_others_character()
    {
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2, 'sanctum')->getJson("/api/characters/{$character->id}");

        $response->assertStatus(403);
    }
}
