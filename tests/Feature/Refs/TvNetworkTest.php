<?php

declare(strict_types=1);

namespace Tests\Feature\Refs;

use App\Exceptions\AppException;
use App\Models\TvNetwork;
use App\Models\User;
use App\Services\TvNetworkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class TvNetworkTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return auth('api')->login(User::factory()->create());
    }

    // ── index ──────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-networks')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->mock(TvNetworkService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $this->withToken($this->token())->getJson('/api/tv-networks')
            ->assertStatus(200)
            ->assertJsonStructure([
                'code', 'message',
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ])
            ->assertJson(['code' => 0]);
    }

    public function test_index_invalid_sort_returns_422(): void
    {
        $this->withToken($this->token())->getJson('/api/tv-networks?sort=name')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_index_per_page_exceeds_max_returns_422(): void
    {
        $this->withToken($this->token())->getJson('/api/tv-networks?per_page=101')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    // ── show ───────────────────────────────────────────────

    public function test_show_returns_network(): void
    {
        $network = (new TvNetwork())->forceFill(['id' => 1, 'tmdb_id' => 213, 'name' => 'Netflix']);

        $this->mock(TvNetworkService::class, function (MockInterface $mock) use ($network) {
            $mock->shouldReceive('findById')
                ->with(1)
                ->once()
                ->andReturn($network);
        });

        $this->withToken($this->token())->getJson('/api/tv-networks/1')
            ->assertStatus(200)
            ->assertJsonStructure(['code', 'message', 'data' => ['id', 'tmdb_id', 'name']])
            ->assertJson(['code' => 0]);
    }

    public function test_show_not_found_returns_404(): void
    {
        $this->mock(TvNetworkService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->with(999)
                ->once()
                ->andThrow(new AppException('电视网络不存在', 404));
        });

        $this->withToken($this->token())->getJson('/api/tv-networks/999')
            ->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_show_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/tv-networks/1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }
}
