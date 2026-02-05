<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso']);

        $this->assertCount(0, $user->tokens);
    }

    // ==========================================================================
    // Security Tests: Unauthenticated Access
    // ==========================================================================

    /**
     * Testa se rotas protegidas retornam 401 para usuários não autenticados.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        // Characters
        $this->getJson('/api/characters')->assertStatus(401);
        $this->postJson('/api/characters', [])->assertStatus(401);
        $this->getJson('/api/characters/1')->assertStatus(401);
        $this->putJson('/api/characters/1', [])->assertStatus(401);
        $this->deleteJson('/api/characters/1')->assertStatus(401);

        // Campaigns
        $this->getJson('/api/campaigns')->assertStatus(401);
        $this->postJson('/api/campaigns', [])->assertStatus(401);
        $this->getJson('/api/campaigns/1')->assertStatus(401);
        $this->postJson('/api/campaigns/join', [])->assertStatus(401);
    }

    /**
     * Testa se token inválido/malformado retorna 401.
     */
    public function test_invalid_token_returns_unauthorized()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_12345',
        ])->getJson('/api/characters');

        $response->assertStatus(401);
    }

    /**
     * Testa se token revogado retorna 401.
     */
    public function test_revoked_token_returns_unauthorized()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        // Revoga o token
        $user->tokens()->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/characters');

        $response->assertStatus(401);
    }
}
