<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthAndMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_assigns_role_and_returns_oauth_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Alice Creator',
            'email' => 'alice@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'organizer',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token',
            ]);

        $user = User::where('email', 'alice@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('organizer'));
        $this->assertNotEmpty($user->uuid);
    }

    public function test_login_issues_token_in_process_and_rejects_invalid_password(): void
    {
        User::create([
            'name' => 'Bob User',
            'email' => 'bob@example.com',
            'password' => 'SecretPass123',
        ]);

        $ok = $this->postJson('/api/v1/login', [
            'email' => 'bob@example.com',
            'password' => 'SecretPass123',
        ]);

        $ok->assertStatus(200)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'refresh_token']);

        $bad = $this->postJson('/api/v1/login', [
            'email' => 'bob@example.com',
            'password' => 'WrongPassword',
        ]);

        $bad->assertStatus(401)
            ->assertJsonPath('errors.0.title', 'Unauthorized Access');
    }

    public function test_me_read_and_update_profile_with_metadata(): void
    {
        $user = User::create([
            'name' => 'Carol Organizer',
            'email' => 'carol@example.com',
            'password' => 'SecretPass123',
            'currency' => 'aud',
        ]);

        Passport::actingAs($user, [], 'api');

        $read = $this->getJson('/api/v1/me');
        $read->assertStatus(200)
            ->assertJsonPath('data.type', 'users')
            ->assertJsonPath('data.id', (string) $user->id)
            ->assertJsonPath('data.attributes.email', 'carol@example.com');

        $update = $this->patchJson('/api/v1/me', [
            'data' => [
                'type' => 'users',
                'id' => (string) $user->id,
                'attributes' => [
                    'name' => 'Carol Updated',
                    'timezone' => 'Australia/Sydney',
                    'metadata' => [
                        'payment_gateway' => 'stripe',
                        'email_notifications' => true,
                        'scotty_module' => true,
                        'scotty_agent_id' => 'agent_42',
                    ],
                ],
            ],
        ]);

        $update->assertStatus(200)
            ->assertJsonPath('data.attributes.name', 'Carol Updated')
            ->assertJsonPath('data.attributes.payment_gateway', 'stripe')
            ->assertJsonPath('data.attributes.email_notifications', true)
            ->assertJsonPath('data.attributes.scotty_module', true)
            ->assertJsonPath('data.attributes.scotty_agent_id', 'agent_42');
    }

    public function test_users_roles_and_permissions_json_api_resources(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'SecretPass123',
        ]);
        $user->assignRole('admin');

        Passport::actingAs($user, [], 'api');

        $usersRes = $this->getJson('/api/v1/users?include=roles');
        $usersRes->assertStatus(200)
            ->assertJsonPath('data.0.type', 'users')
            ->assertJsonStructure(['data', 'included']);

        $rolesRes = $this->getJson('/api/v1/roles');
        $rolesRes->assertStatus(200)
            ->assertJsonPath('data.0.type', 'roles');

        $permsRes = $this->getJson('/api/v1/permissions');
        $permsRes->assertStatus(200)
            ->assertJsonPath('data.0.type', 'permissions');
    }
}
