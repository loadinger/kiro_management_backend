<?php

declare(strict_types=1);

namespace Tests\Feature\Movies;

use App\Enums\CreditType;
use App\Models\MovieCredit;
use App\Models\User;
use App\Services\MovieCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class MovieCreditListTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/movie-credits?movie_id=1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_missing_movie_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movie-credits');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_person_is_null_when_person_id_is_null(): void
    {
        $credit = (new MovieCredit)->forceFill([
            'id' => 1,
            'movie_id' => 100,
            'person_tmdb_id' => 999,
            'person_id' => null,
            'credit_type' => CreditType::Cast,
            'character' => 'Hero',
            'cast_order' => 1,
            'department_id' => null,
            'job_id' => null,
        ]);

        $paginator = new LengthAwarePaginator([$credit], 1, 20, 1);

        $this->mock(MovieCreditService::class, function (MockInterface $mock) use ($paginator) {
            $mock->shouldReceive('getList')->once()->andReturn($paginator);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movie-credits?movie_id=100');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.list.0.person', null);
    }

    public function test_returns_paginated_credit_list(): void
    {
        $credit = (new MovieCredit)->forceFill([
            'id' => 1,
            'movie_id' => 100,
            'person_tmdb_id' => 999,
            'person_id' => null,
            'credit_type' => CreditType::Cast,
            'character' => 'Hero',
            'cast_order' => 1,
            'department_id' => null,
            'job_id' => null,
        ]);

        $paginator = new LengthAwarePaginator([$credit], 1, 20, 1);

        $this->mock(MovieCreditService::class, function (MockInterface $mock) use ($paginator) {
            $mock->shouldReceive('getList')->once()->andReturn($paginator);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movie-credits?movie_id=100');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'list' => [
                        '*' => [
                            'id',
                            'movie_id',
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
}
