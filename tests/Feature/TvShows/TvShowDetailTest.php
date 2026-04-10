<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Exceptions\AppException;
use App\Models\TvShow;
use App\Models\User;
use App\Services\TvShowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TvShowDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/tv-shows/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_404_when_tv_show_not_found(): void
    {
        $this->mock(TvShowService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->andThrow(new AppException('电视剧不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-shows/999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_returns_tv_show_detail_with_all_fields(): void
    {
        $tvShow = (new TvShow)->forceFill([
            'id' => 1,
            'tmdb_id' => 12345,
            'name' => '测试电视剧',
            'original_name' => 'Test TV Show',
            'original_language' => 'en',
            'overview' => 'A test tv show overview.',
            'tagline' => 'Just a test.',
            'status' => 'Returning Series',
            'type' => 'Scripted',
            'first_air_date' => '2020-01-01',
            'last_air_date' => '2023-12-31',
            'number_of_seasons' => 3,
            'number_of_episodes' => 30,
            'episode_run_time' => [45],
            'popularity' => 88.5,
            'vote_average' => 8.2,
            'vote_count' => 5000,
            'adult' => false,
            'in_production' => true,
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'homepage' => 'https://example.com',
            'origin_country_codes' => ['US'],
            'spoken_language_codes' => ['en'],
            'language_codes' => ['en'],
            'production_country_codes' => ['US'],
            'last_episode_to_air' => null,
            'next_episode_to_air' => null,
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2023-12-31T00:00:00Z',
        ]);

        $this->mock(TvShowService::class, function (MockInterface $mock) use ($tvShow) {
            $mock->shouldReceive('findById')->once()->andReturn($tvShow);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-shows/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tmdb_id',
                    'name',
                    'original_name',
                    'original_language',
                    'overview',
                    'tagline',
                    'status',
                    'type',
                    'first_air_date',
                    'last_air_date',
                    'number_of_seasons',
                    'number_of_episodes',
                    'episode_run_time',
                    'popularity',
                    'vote_average',
                    'vote_count',
                    'adult',
                    'in_production',
                    'poster_path',
                    'backdrop_path',
                    'homepage',
                    'origin_country_codes',
                    'spoken_language_codes',
                    'language_codes',
                    'production_country_codes',
                    'last_episode_to_air',
                    'next_episode_to_air',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.name', '测试电视剧');
    }
}
