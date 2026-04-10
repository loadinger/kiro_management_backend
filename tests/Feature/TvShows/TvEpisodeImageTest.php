<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Models\User;
use App\Services\TvEpisodeImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class TvEpisodeImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-episode-images?tv_episode_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_missing_tv_episode_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-images')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_returns_paginated_episode_image_list(): void
    {
        $this->mock(TvEpisodeImageService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-images?tv_episode_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    public function test_page_over_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-images?tv_episode_id=1&page=1001')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }
}
