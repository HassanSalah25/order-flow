<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'jane@example.com');
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_is_validated(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => 'jane@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => 'jane@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Invalid email or password.']);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->actingAsJwt();

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/payments')->assertUnauthorized();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/orders')
            ->assertUnauthorized();
    }

    public function test_logout_invalidates_the_token(): void
    {
        $this->actingAsJwt();

        $this->postJson('/api/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }
}
