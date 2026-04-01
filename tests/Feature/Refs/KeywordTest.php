<?php

declare(strict_types=1);

namespace Tests\Feature\Refs;

use App\Models\User;
use App\Services\KeywordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class KeywordTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return auth('api')->login(User::factory()->create());
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/keywords')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->mock(KeywordService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $this->withToken($this->token())->getJson('/api/keywords')
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
        $this->withToken($this->token())->getJson('/api/keywords?sort=name')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_index_per_page_exceeds_max_returns_422(): void
    {
        $this->withToken($this->token())->getJson('/api/keywords?per_page=101')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_all_returns_flat_array(): void
    {
        $this->mock(KeywordService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAll')
                ->once()
                ->andReturn(new Collection());
        });

        $this->withToken($this->token())->getJson('/api/keywords/all')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    public function test_all_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/keywords/all')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }
}
