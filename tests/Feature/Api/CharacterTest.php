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

    // ==========================================================================
    // Security Tests: Authorization
    // ==========================================================================

    /**
     * Testa se User A NÃO pode ATUALIZAR o personagem do User B.
     */
    public function test_user_cannot_update_other_users_character()
    {
        $userA = User::factory()->create();
        /** @var User $userB */
        $userB = User::factory()->create();
        $characterB = Character::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/characters/{$characterB->id}", [
                'name' => 'Hacked Name'
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('characters', ['id' => $characterB->id, 'name' => 'Hacked Name']);
    }

    /**
     * Testa se User A NÃO pode DELETAR o personagem do User B.
     */
    public function test_user_cannot_delete_other_users_character()
    {
        $userA = User::factory()->create();
        /** @var User $userB */
        $userB = User::factory()->create();
        $characterB = Character::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/characters/{$characterB->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('characters', ['id' => $characterB->id]);
    }

    // ==========================================================================
    // Validation Tests: Invalid Enums
    // ==========================================================================

    /**
     * Testa rejeição de raça inválida (não existe no enum CharacterRace).
     */
    public function test_cannot_create_character_with_invalid_race()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['race'] = 'alien'; // Não existe no enum

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['race']);
    }

    /**
     * Testa rejeição de classe inválida (não existe no enum CharacterClass).
     */
    public function test_cannot_create_character_with_invalid_class()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['class'] = 'necromancer'; // Não existe no enum

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['class']);
    }

    // ==========================================================================
    // Validation Tests: Boundary Values
    // ==========================================================================

    /**
     * Testa rejeição de nome de personagem vazio.
     */
    public function test_cannot_create_character_with_empty_name()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['name'] = '';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Testa rejeição de nome de personagem excessivamente longo (> 255 chars).
     */
    public function test_cannot_create_character_with_extremely_long_name()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['name'] = str_repeat('A', 256);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Testa rejeição de nível zero (mínimo é 1).
     */
    public function test_cannot_create_character_with_zero_level()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['level'] = 0;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    /**
     * Testa rejeição de nível acima do máximo (> 20).
     */
    public function test_cannot_create_character_with_level_above_maximum()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['level'] = 21;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    /**
     * Testa rejeição de hit_points zero (mínimo é 1).
     */
    public function test_cannot_create_character_with_zero_hit_points()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $data = $this->getValidCharacterData();
        $data['hit_points'] = 0;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hit_points']);
    }

    /**
     * Testa rejeição de atributos fora do range (1-30).
     */
    public function test_cannot_create_character_with_attributes_out_of_range()
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Atributo abaixo do mínimo
        $data = $this->getValidCharacterData();
        $data['strength'] = 0;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['strength']);

        // Atributo acima do máximo
        $data['strength'] = 31;

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/characters', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['strength']);
    }

    // ==========================================================================
    // Resilience Tests: 404 Responses
    // ==========================================================================

    /**
     * Testa se GET para personagem inexistente retorna 404.
     */
    public function test_get_nonexistent_character_returns_404()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/characters/99999');

        $response->assertStatus(404);
    }

    /**
     * Testa se PUT para personagem inexistente retorna 404.
     */
    public function test_update_nonexistent_character_returns_404()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/characters/99999', ['name' => 'Ghost']);

        $response->assertStatus(404);
    }

    /**
     * Testa se DELETE para personagem inexistente retorna 404.
     */
    public function test_delete_nonexistent_character_returns_404()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/characters/99999');

        $response->assertStatus(404);
    }

    // ==========================================================================
    // Helper Methods
    // ==========================================================================

    /**
     * Retorna dados válidos para criação de personagem.
     */
    private function getValidCharacterData(): array
    {
        return [
            'name' => 'Valid Character',
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
    }
}
