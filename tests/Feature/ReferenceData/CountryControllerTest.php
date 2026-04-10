<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Models\User;
use App\Services\CountryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    // Feature: reference-data, Property 1: unauthenticated request returns 401
    // Validates: Requirements 1.1, 1.2
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/countries');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Feature: reference-data, Property 2: valid token can access endpoint
    // Feature: reference-data, Property 3: list response structure integrity
    // Validates: Requirements 1.3, 2.1, 2.2, 3.4
    public function test_index_returns_paginated_list_with_correct_structure(): void
    {
        $this->mock(CountryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20, 1));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/countries');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'items',
                    'meta' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    // Feature: reference-data, Property 4: per_page exceeding 100 returns 422
    // Validates: Requirements 3.2
    public function test_per_page_exceeding_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/countries?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 5: page exceeding 1000 returns 422
    // Validates: Requirements 3.3
    public function test_page_exceeding_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/countries?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 7: q exceeding 100 chars returns 422
    // Validates: Requirements 4.3
    public function test_q_exceeding_100_chars_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $longQ = str_repeat('a', 101);
        $response = $this->withToken($token)->getJson('/api/countries?q='.$longQ);

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 8: invalid sort field returns 422
    // Validates: Requirements 4.4, 13.2
    public function test_invalid_sort_field_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/countries?sort=invalid_field');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }
}
