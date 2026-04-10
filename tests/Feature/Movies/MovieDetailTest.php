<?php

declare(strict_types=1);

namespace Tests\Feature\Movies;

use App\Exceptions\AppException;
use App\Models\Movie;
use App\Models\User;
use App\Services\MovieService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MovieDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/movies/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401]);
    }

    public function test_returns_404_when_movie_not_found(): void
    {
        $this->mock(MovieService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->andThrow(new AppException('电影不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies/999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404]);
    }

    public function test_returns_movie_detail(): void
    {
        $movie = (new Movie)->forceFill([
            'id' => 1,
            'tmdb_id' => 12345,
            'imdb_id' => 'tt1234567',
            'title' => '测试电影',
            'original_title' => 'Test Movie',
            'original_language' => 'en',
            'overview' => 'A test movie overview.',
            'tagline' => 'Just a test.',
            'status' => 'Released',
            'release_date' => '2023-01-01',
            'runtime' => 120,
            'budget' => 10000000,
            'revenue' => 50000000,
            'popularity' => 99.5,
            'vote_average' => 7.8,
            'vote_count' => 1000,
            'adult' => false,
            'video' => false,
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'homepage' => 'https://example.com',
            'spoken_language_codes' => ['en', 'zh'],
            'production_country_codes' => ['US'],
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-02T00:00:00Z',
        ]);

        $this->mock(MovieService::class, function (MockInterface $mock) use ($movie) {
            $mock->shouldReceive('findById')->once()->andReturn($movie);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/movies/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tmdb_id',
                    'imdb_id',
                    'title',
                    'original_title',
                    'original_language',
                    'overview',
                    'tagline',
                    'status',
                    'release_date',
                    'runtime',
                    'budget',
                    'revenue',
                    'popularity',
                    'vote_average',
                    'vote_count',
                    'adult',
                    'video',
                    'poster_path',
                    'backdrop_path',
                    'homepage',
                    'spoken_language_codes',
                    'production_country_codes',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.tmdb_id', 12345)
            ->assertJsonPath('data.title', '测试电影');
    }
}
