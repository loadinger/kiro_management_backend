<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Exceptions\AppException;
use App\Models\TvSeason;
use App\Models\User;
use App\Services\TvSeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TvSeasonDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-seasons/1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_404_when_season_not_found(): void
    {
        $this->mock(TvSeasonService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->andThrow(new AppException('季不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-seasons/999')
            ->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_returns_season_detail(): void
    {
        $season = (new TvSeason)->forceFill([
            'id' => 1,
            'tv_show_id' => 10,
            'tmdb_id' => 55555,
            'season_number' => 1,
            'name' => 'Season 1',
            'overview' => 'First season overview.',
            'air_date' => '2020-01-01',
            'episode_count' => 10,
            'vote_average' => 8.0,
            'poster_path' => '/season1.jpg',
        ]);

        $this->mock(TvSeasonService::class, function (MockInterface $mock) use ($season) {
            $mock->shouldReceive('findById')->once()->andReturn($season);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-seasons/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tv_show_id',
                    'tmdb_id',
                    'season_number',
                    'name',
                    'overview',
                    'air_date',
                    'episode_count',
                    'vote_average',
                    'poster_path',
                ],
            ])
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.season_number', 1);
    }
}
