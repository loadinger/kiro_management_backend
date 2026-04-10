<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class CollectionListTest extends TestCase
{
    use RefreshDatabase;

    // Validates: Requirements 1.3, 3.1
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/collections');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Validates: Requirements 1.2, 1.9, 1.12
    public function test_returns_paginated_collection_list(): void
    {
        $this->mock(CollectionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20, 1));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    // Validates: Requirements 1.5, 1.7
    public function test_page_over_limit_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Validates: Requirements 1.6, 1.8
    public function test_per_page_over_limit_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Validates: Requirements 3.3
    public function test_non_integer_params_return_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections?page=abc');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Validates: Requirements 3.4
    public function test_invalid_order_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections?order=random');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Validates: Requirements 3.2
    public function test_q_too_long_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $longQ = str_repeat('a', 101);
        $response = $this->withToken($token)->getJson('/api/collections?q='.$longQ);

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }
}
