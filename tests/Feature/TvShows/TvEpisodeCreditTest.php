<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Enums\CreditType;
use App\Models\TvEpisodeCredit;
use App\Models\User;
use App\Services\TvEpisodeCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class TvEpisodeCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-episode-credits?tv_episode_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_missing_tv_episode_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-credits')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_invalid_credit_type_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-credits?tv_episode_id=1&credit_type=invalid')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_returns_paginated_credit_list(): void
    {
        $credit = (new TvEpisodeCredit)->forceFill([
            'id' => 1,
            'tv_episode_id' => 100,
            'person_tmdb_id' => 999,
            'person_id' => null,
            'credit_type' => CreditType::Cast,
            'character' => 'Hero',
            'cast_order' => 1,
            'department_id' => null,
            'job_id' => null,
        ]);

        $paginator = new LengthAwarePaginator([$credit], 1, 20, 1);

        $this->mock(TvEpisodeCreditService::class, function (MockInterface $mock) use ($paginator) {
            $mock->shouldReceive('getList')->once()->andReturn($paginator);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-credits?tv_episode_id=100')
            ->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'list' => [
                        '*' => [
                            'id',
                            'tv_episode_id',
                            'person_tmdb_id',
                            'person_id',
                            'credit_type',
                            'character',
                            'cast_order',
                            'department_id',
                            'job_id',
                            'person',
                        ],
                    ],
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    public function test_person_is_null_when_person_id_is_null_and_record_is_not_filtered(): void
    {
        // Credit with person_id = null (async reconciliation pending)
        $credit = (new TvEpisodeCredit)->forceFill([
            'id' => 1,
            'tv_episode_id' => 100,
            'person_tmdb_id' => 999,
            'person_id' => null,
            'credit_type' => CreditType::Cast,
            'character' => 'Hero',
            'cast_order' => 1,
            'department_id' => null,
            'job_id' => null,
        ]);

        $paginator = new LengthAwarePaginator([$credit], 1, 20, 1);

        $this->mock(TvEpisodeCreditService::class, function (MockInterface $mock) use ($paginator) {
            $mock->shouldReceive('getList')->once()->andReturn($paginator);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-episode-credits?tv_episode_id=100');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            // Record is not filtered out — list has exactly 1 item
            ->assertJsonCount(1, 'data.list')
            // person field is null for unresolved async association
            ->assertJsonPath('data.list.0.person', null)
            ->assertJsonPath('data.list.0.person_id', null)
            ->assertJsonPath('data.list.0.person_tmdb_id', 999);
    }

    public function test_page_over_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episode-credits?tv_episode_id=1&page=1001')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }
}
