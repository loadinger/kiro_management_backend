<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Exceptions\AppException;
use App\Models\TvNetwork;
use App\Models\User;
use App\Services\TvNetworkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class TvNetworkControllerTest extends TestCase
{
    use RefreshDatabase;

    // Feature: reference-data, Property 1: unauthenticated request returns 401
    // Validates: Requirements 1.1, 1.2
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/tv-networks');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Feature: reference-data, Property 2: valid token can access endpoint
    // Feature: reference-data, Property 3: list response structure integrity
    // Validates: Requirements 1.3, 2.1, 2.2, 3.4
    public function test_index_returns_paginated_list_with_correct_structure(): void
    {
        $this->mock(TvNetworkService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20, 1));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks');

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

    // Feature: reference-data, Property 4: per_page exceeding 100 returns 422
    // Validates: Requirements 3.2, 11.3
    public function test_per_page_exceeding_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 5: page exceeding 1000 returns 422
    // Validates: Requirements 3.3, 11.8
    public function test_page_exceeding_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 7: q exceeding 100 chars returns 422
    // Validates: Requirements 11.3
    public function test_q_exceeding_100_chars_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $longQ = str_repeat('a', 101);
        $response = $this->withToken($token)->getJson('/api/tv-networks?q='.$longQ);

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 8: invalid sort field returns 422
    // Validates: Requirements 11.8, 13.2
    public function test_invalid_sort_field_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks?sort=invalid_field');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 14: show returns 404 when not found
    // Validates: Requirements 2.4, 11.5
    public function test_show_returns_404_when_not_found(): void
    {
        $this->mock(TvNetworkService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->with(9999)
                ->andThrow(new AppException('电视网络不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks/9999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404, 'message' => '电视网络不存在', 'data' => null]);
    }

    // Feature: reference-data, Property 15: show returns full detail fields with w342 logo_url
    // Validates: Requirements 11.4, 11.6
    public function test_show_returns_full_detail_fields(): void
    {
        $network = (new TvNetwork)->forceFill([
            'id' => 1,
            'tmdb_id' => 213,
            'name' => 'Netflix',
            'headquarters' => 'Los Gatos, California',
            'homepage' => 'https://netflix.com',
            'logo_path' => '/wwemzKWzjKYJFfCeiB57q3r4Bcm.png',
            'origin_country' => 'US',
        ]);

        $this->mock(TvNetworkService::class, function (MockInterface $mock) use ($network) {
            $mock->shouldReceive('findById')
                ->once()
                ->with(1)
                ->andReturn($network);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => ['id', 'tmdb_id', 'name', 'headquarters', 'homepage', 'logo_path', 'origin_country'],
            ]);

        $logoUrl = $response->json('data.logo_path');
        $this->assertStringContainsString('w342', $logoUrl);
    }

    // Feature: reference-data, Property 13: logo_path null returns logo_path null
    // Validates: Requirements 11.7
    public function test_logo_url_is_null_when_logo_path_is_null(): void
    {
        $network = (new TvNetwork)->forceFill([
            'id' => 2,
            'tmdb_id' => 214,
            'name' => 'No Logo Network',
            'headquarters' => null,
            'homepage' => null,
            'logo_path' => null,
            'origin_country' => 'US',
        ]);

        $this->mock(TvNetworkService::class, function (MockInterface $mock) use ($network) {
            $mock->shouldReceive('findById')
                ->once()
                ->with(2)
                ->andReturn($network);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-networks/2');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJson(['data' => ['logo_path' => null]]);
    }
}
