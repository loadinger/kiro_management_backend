<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in'],
            ])
            ->assertJsonPath('data.token_type', 'bearer');
    }

    public function test_login_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'message' => '邮箱或密码错误']);
    }

    public function test_login_with_missing_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_login_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Logout succeeds
        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => '已退出登录']);

        // Blacklisted token is rejected on subsequent requests
        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_logout_without_token(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    // -------------------------------------------------------------------------
    // Refresh
    // -------------------------------------------------------------------------

    public function test_refresh_returns_new_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'expires_in'],
            ]);

        // New token must differ from the original
        $this->assertNotEquals($token, $response->json('data.access_token'));
    }

    public function test_refresh_without_token(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    // -------------------------------------------------------------------------
    // Me
    // -------------------------------------------------------------------------

    public function test_me_returns_user_info(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at'],
            ]);

        // Sensitive fields must not be present
        $data = $response->json('data');
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
    }

    public function test_me_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }
}
