<?php

declare(strict_types=1);

namespace Tests\Feature\Movies;

use App\Models\User;
use App\Services\MovieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class MovieListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/movies');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_page_over_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_per_page_over_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_returns_paginated_movie_list(): void
    {
        $this->mock(MovieService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }
}
