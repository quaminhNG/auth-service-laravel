<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Testuser',
            'email' => 'Testuser@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(
                [
                    'message',
                    'user'
                ]
            );
        //check database
        $this->assertDatabaseHas('users', [
            'email' => 'Testuser@gmail.com'
        ]);
    }
    public function test_user_can_login()
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'user',
                    'message',
                    'access_token',
                    'refresh_token'
                ]
            );
    }
    public function test_user_cannot_access_with_wrong_role()
    {
        $user = User::factory()->create([
            'role' => UserRole::USER->value
        ]);
        $token = TokenService::generateAccessToken($user);
        $response = $this->withHeader('Authorization', "Bearer $token")->getJson('/api/admin/dashboard');
        $response->assertStatus(403);
    }
    public function test_rate_limiting_on_login()
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ])->assertStatus(401);
        }
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(429);
    }
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = TokenService::generateAccessToken($user);
        $refresh = TokenService::generateRefreshToken($user, $token);
        $response = $this->postJson('/api/refresh', [
            'refresh_token' => $refresh
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure(
                [
                    'access_token',
                    'refresh_token',
                ]
            );
        $this->assertDatabaseHas('refresh_tokens', [
            'token' => $refresh,
            'is_revoked' => true
        ]);
    }
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = TokenService::generateAccessToken($user);
        $refresh = TokenService::generateRefreshToken($user, $token);
        $response = $this->withHeader('Authorization', "Bearer $token")->postJson('/api/logout', [
            'refresh_token' => $refresh
        ]);
        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        $this->assertDatabaseHas('refresh_tokens', [
            'token' => $refresh,
            'is_revoked' => true
        ]);
    }
    public function test_admin_can_access_dashboard()
    {
        $admin = User::factory()->create(
            [
                'role' => UserRole::ADMIN->value
            ]
        );
        $token = TokenService::generateAccessToken($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertSee('Admin!');
    }
}
