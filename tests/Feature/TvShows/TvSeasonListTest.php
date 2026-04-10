<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Models\User;
use App\Services\TvSeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class TvSeasonListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-seasons?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_missing_tv_show_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_returns_paginated_season_list(): void
    {
        $this->mock(TvSeasonService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    public function test_invalid_sort_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons?tv_show_id=1&sort=invalid_field')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_page_over_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons?tv_show_id=1&page=1001')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_per_page_over_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons?tv_show_id=1&per_page=101')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }
}
