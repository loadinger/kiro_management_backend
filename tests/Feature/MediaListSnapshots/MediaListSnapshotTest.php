<?php

declare(strict_types=1);

namespace Tests\Feature\MediaListSnapshots;

use App\Models\User;
use App\Services\MediaListSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaListSnapshotTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string> */
    private array $allEndpoints = [
        '/api/media-lists/movie-now-playing',
        '/api/media-lists/movie-upcoming',
        '/api/media-lists/movie-trending-day',
        '/api/media-lists/movie-trending-week',
        '/api/media-lists/tv-airing-today',
        '/api/media-lists/tv-on-the-air',
        '/api/media-lists/tv-trending-day',
        '/api/media-lists/tv-trending-week',
        '/api/media-lists/person-trending-day',
        '/api/media-lists/person-trending-week',
    ];

    public function test_unauthenticated_request_returns_401(): void
    {
        foreach ($this->allEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $response->assertStatus(200)
                ->assertJson(['code' => 401]);
        }
    }

    public function test_returns_movie_list_with_correct_structure(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMovieList')
                ->andReturn([
                    'list' => [
                        [
                            'rank' => 1,
                            'popularity' => '1234.567',
                            'snapshot_date' => '2025-01-15',
                            'tmdb_id' => 12345,
                            'local_id' => 678,
                            'id' => 678,
                            'title' => '某电影',
                            'original_title' => 'Some Movie',
                            'release_date' => '2024-11-20',
                            'poster_path' => '/abc123.jpg',
                            'vote_average' => 7.8,
                            'status' => 'Released',
                        ],
                    ],
                    'snapshot_date' => '2025-01-15',
                ]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'list' => [
                        '*' => [
                            'rank', 'popularity', 'snapshot_date', 'tmdb_id', 'local_id',
                            'id', 'title', 'original_title', 'release_date', 'poster_path',
                            'vote_average', 'status',
                        ],
                    ],
                    'snapshot_date',
                ],
            ])
            ->assertJsonPath('data.snapshot_date', '2025-01-15')
            ->assertJsonPath('data.list.0.rank', 1)
            ->assertJsonPath('data.list.0.title', '某电影');
    }

    public function test_returns_tv_show_list_with_correct_structure(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTvShowList')
                ->andReturn([
                    'list' => [
                        [
                            'rank' => 1,
                            'popularity' => '500.123',
                            'snapshot_date' => '2025-01-15',
                            'tmdb_id' => 67890,
                            'local_id' => 123,
                            'id' => 123,
                            'name' => '某剧集',
                            'original_name' => 'Some Show',
                            'first_air_date' => '2023-05-10',
                            'poster_path' => '/xyz.jpg',
                            'vote_average' => 8.2,
                            'status' => 'Returning Series',
                        ],
                    ],
                    'snapshot_date' => '2025-01-15',
                ]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/tv-airing-today');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'list' => [
                        '*' => [
                            'rank', 'popularity', 'snapshot_date', 'tmdb_id', 'local_id',
                            'id', 'name', 'original_name', 'first_air_date', 'poster_path',
                            'vote_average', 'status',
                        ],
                    ],
                    'snapshot_date',
                ],
            ])
            ->assertJsonPath('data.snapshot_date', '2025-01-15')
            ->assertJsonPath('data.list.0.name', '某剧集');
    }

    public function test_returns_person_list_with_correct_structure(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getPersonList')
                ->andReturn([
                    'list' => [
                        [
                            'rank' => 1,
                            'popularity' => '300.456',
                            'snapshot_date' => '2025-01-15',
                            'tmdb_id' => 11111,
                            'local_id' => 222,
                            'id' => 222,
                            'name' => '某演员',
                            'known_for_department' => 'Acting',
                            'profile_path' => '/profile.jpg',
                            'gender' => 2,
                        ],
                    ],
                    'snapshot_date' => '2025-01-15',
                ]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/person-trending-day');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'list' => [
                        '*' => [
                            'rank', 'popularity', 'snapshot_date', 'tmdb_id', 'local_id',
                            'id', 'name', 'known_for_department', 'profile_path', 'gender',
                        ],
                    ],
                    'snapshot_date',
                ],
            ])
            ->assertJsonPath('data.snapshot_date', '2025-01-15')
            ->assertJsonPath('data.list.0.name', '某演员');
    }

    public function test_invalid_snapshot_date_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing?snapshot_date=2025/01/15');

        $response->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_empty_list_returns_resolved_snapshot_date(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            // Simulates: date exists in snapshot table but all entries have null local_id (filtered by INNER JOIN)
            $mock->shouldReceive('getMovieList')
                ->andReturn(['list' => [], 'snapshot_date' => '2025-01-15']);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.list', [])
            ->assertJsonPath('data.snapshot_date', '2025-01-15');
    }

    public function test_snapshot_date_is_null_when_no_data_exists(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            // Simulates: list_type has no data at all in the snapshot table
            $mock->shouldReceive('getMovieList')
                ->andReturn(['list' => [], 'snapshot_date' => null]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.list', [])
            ->assertJsonPath('data.snapshot_date', null);
    }

    public function test_entity_fields_are_null_when_entity_not_found(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMovieList')
                ->andReturn([
                    'list' => [
                        [
                            'rank' => 1,
                            'popularity' => '100.000',
                            'snapshot_date' => '2025-01-15',
                            'tmdb_id' => 99999,
                            'local_id' => null,
                            'id' => null,
                            'title' => null,
                            'original_title' => null,
                            'release_date' => null,
                            'poster_path' => null,
                            'vote_average' => null,
                            'status' => null,
                        ],
                    ],
                    'snapshot_date' => '2025-01-15',
                ]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.list.0.id', null)
            ->assertJsonPath('data.list.0.title', null)
            ->assertJsonPath('data.list.0.poster_path', null)
            ->assertJsonPath('data.list.0.tmdb_id', 99999);
    }

    public function test_poster_path_is_null_when_poster_path_is_null(): void
    {
        $this->mock(MediaListSnapshotService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getMovieList')
                ->andReturn([
                    'list' => [
                        [
                            'rank' => 1,
                            'popularity' => '200.000',
                            'snapshot_date' => '2025-01-15',
                            'tmdb_id' => 55555,
                            'local_id' => 333,
                            'id' => 333,
                            'title' => '无海报电影',
                            'original_title' => 'No Poster Movie',
                            'release_date' => '2024-06-01',
                            'poster_path' => null,
                            'vote_average' => 6.5,
                            'status' => 'Released',
                        ],
                    ],
                    'snapshot_date' => '2025-01-15',
                ]);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/media-lists/movie-now-playing');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.list.0.poster_path', null);
    }
}
