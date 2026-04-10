<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Exceptions\AppException;
use App\Models\TvEpisode;
use App\Models\User;
use App\Services\TvEpisodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TvEpisodeDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/tv-episodes/1')
            ->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_404_when_episode_not_found(): void
    {
        $this->mock(TvEpisodeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->andThrow(new AppException('集不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-episodes/999')
            ->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_returns_episode_detail(): void
    {
        $episode = (new TvEpisode)->forceFill([
            'id' => 1,
            'tv_show_id' => 10,
            'tv_season_id' => 20,
            'tmdb_id' => 77777,
            'season_number' => 1,
            'episode_number' => 3,
            'episode_type' => 'standard',
            'production_code' => 'EP103',
            'name' => 'Episode 3',
            'overview' => 'Third episode overview.',
            'air_date' => '2020-01-15',
            'runtime' => 45,
            'vote_average' => 7.5,
            'vote_count' => 200,
            'still_path' => '/still3.jpg',
        ]);

        $this->mock(TvEpisodeService::class, function (MockInterface $mock) use ($episode) {
            $mock->shouldReceive('findById')->once()->andReturn($episode);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-episodes/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tv_show_id',
                    'tv_season_id',
                    'tmdb_id',
                    'season_number',
                    'episode_number',
                    'episode_type',
                    'production_code',
                    'name',
                    'overview',
                    'air_date',
                    'runtime',
                    'vote_average',
                    'vote_count',
                    'still_path',
                ],
            ])
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.episode_number', 3);
    }
}
