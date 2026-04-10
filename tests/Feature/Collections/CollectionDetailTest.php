<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Exceptions\AppException;
use App\Models\Collection;
use App\Models\CollectionMovie;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CollectionDetailTest extends TestCase
{
    use RefreshDatabase;

    // Validates: Requirements 2.3, 3.1
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/collections/1');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Validates: Requirements 2.2, 2.5
    public function test_returns_collection_detail_with_movies(): void
    {
        $movie = (new CollectionMovie)->forceFill([
            'id' => 1,
            'collection_id' => 10,
            'movie_tmdb_id' => 299536,
            'movie_id' => 500,
        ]);

        $collection = (new Collection)->forceFill([
            'id' => 10,
            'tmdb_id' => 86311,
            'name' => 'The Avengers Collection',
            'overview' => 'Earth\'s mightiest heroes.',
            'poster_path' => '/yFSIUVTCvgYrpalUktulvk3Gi5Y.jpg',
            'backdrop_path' => '/zuW6fOiusv4X9nnW3paHGfXcSll.jpg',
        ]);

        $collection->setRelation('movies', new EloquentCollection([$movie]));

        $this->mock(CollectionService::class, function (MockInterface $mock) use ($collection) {
            $mock->shouldReceive('findById')
                ->once()
                ->with(10)
                ->andReturn($collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections/10');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'tmdb_id',
                    'name',
                    'overview',
                    'poster_path',
                    'backdrop_path',
                    'movies' => [
                        '*' => ['movie_tmdb_id', 'movie_id', 'resolved'],
                    ],
                ],
            ]);

        $this->assertTrue($response->json('data.movies.0.resolved'));
    }

    // Validates: Requirements 2.4
    public function test_returns_404_when_collection_not_found(): void
    {
        $this->mock(CollectionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findById')
                ->once()
                ->with(9999)
                ->andThrow(new AppException('合集不存在', 404));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/collections/9999');

        $response->assertStatus(200)
            ->assertJson(['code' => 404, 'message' => '合集不存在', 'data' => null]);
    }
}
