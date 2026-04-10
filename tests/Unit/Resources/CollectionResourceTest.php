<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Helpers\ImageHelper;
use App\Http\Resources\CollectionListResource;
use App\Http\Resources\CollectionMovieResource;
use App\Http\Resources\CollectionResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for Collection API Resources.
 *
 * Tests use anonymous Model instances — no database connection required.
 * Validates Properties 1–5 from the design document.
 */
class CollectionResourceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Property 1: CollectionListResource field completeness (Requirement 1.9)
    // -------------------------------------------------------------------------

    /**
     * CollectionListResource must output exactly the five expected fields.
     *
     * Validates: Requirements 1.9
     */
    public function test_collection_list_resource_contains_required_fields(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'The Avengers Collection',
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
        ]);

        $resource = new CollectionListResource($model);
        $data = $resource->toArray(new Request);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('tmdb_id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('poster_path', $data);
        $this->assertArrayHasKey('backdrop_path', $data);
        $this->assertCount(5, $data);
    }

    // -------------------------------------------------------------------------
    // Property 2: CollectionListResource image URL sizes (Requirements 1.10, 1.11)
    // -------------------------------------------------------------------------

    /**
     * poster_path must use w342 and backdrop_path must use w780 in list resource.
     *
     * Validates: Requirements 1.10, 1.11
     */
    public function test_collection_list_resource_uses_correct_image_sizes(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'Test Collection',
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
        ]);

        $resource = new CollectionListResource($model);
        $data = $resource->toArray(new Request);

        $this->assertSame(
            ImageHelper::url('/poster.jpg', 'w342'),
            $data['poster_path'],
            'List poster_path must use w342'
        );
        $this->assertSame(
            ImageHelper::url('/backdrop.jpg', 'w780'),
            $data['backdrop_path'],
            'List backdrop_path must use w780'
        );
        $this->assertStringContainsString('w342', (string) $data['poster_path']);
        $this->assertStringContainsString('w780', (string) $data['backdrop_path']);
    }

    /**
     * When poster_path or backdrop_path is null, the corresponding URL must be null.
     *
     * Validates: Requirements 1.10, 1.11
     */
    public function test_collection_list_resource_returns_null_urls_when_paths_are_null(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'Test Collection',
            'poster_path' => null,
            'backdrop_path' => null,
        ]);

        $resource = new CollectionListResource($model);
        $data = $resource->toArray(new Request);

        $this->assertNull($data['poster_path'], 'poster_path must be null when poster_path is null');
        $this->assertNull($data['backdrop_path'], 'backdrop_path must be null when backdrop_path is null');
    }

    // -------------------------------------------------------------------------
    // Property 3: CollectionResource field completeness (Requirement 2.5)
    // -------------------------------------------------------------------------

    /**
     * CollectionResource must output exactly the seven expected fields.
     *
     * Validates: Requirements 2.5
     */
    public function test_collection_resource_contains_required_fields(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'The Avengers Collection',
            'overview' => 'A collection of Avengers films.',
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
        ]);

        // Simulate whenLoaded('movies') returning an empty collection
        $model->setRelation('movies', collect([]));

        $resource = new CollectionResource($model);
        $data = $resource->toArray(new Request);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('tmdb_id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('overview', $data);
        $this->assertArrayHasKey('poster_path', $data);
        $this->assertArrayHasKey('backdrop_path', $data);
        $this->assertArrayHasKey('movies', $data);
        $this->assertCount(7, $data);
    }

    // -------------------------------------------------------------------------
    // Property 4: CollectionResource image URL sizes (Requirements 2.6, 2.7)
    // -------------------------------------------------------------------------

    /**
     * poster_path must use w500 and backdrop_path must use original in detail resource.
     *
     * Validates: Requirements 2.6, 2.7
     */
    public function test_collection_resource_uses_correct_image_sizes(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'Test Collection',
            'overview' => null,
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
        ]);

        $model->setRelation('movies', collect([]));

        $resource = new CollectionResource($model);
        $data = $resource->toArray(new Request);

        $this->assertSame(
            ImageHelper::url('/poster.jpg', 'w500'),
            $data['poster_path'],
            'Detail poster_path must use w500'
        );
        $this->assertSame(
            ImageHelper::url('/backdrop.jpg', 'original'),
            $data['backdrop_path'],
            'Detail backdrop_path must use original'
        );
        $this->assertStringContainsString('w500', (string) $data['poster_path']);
        $this->assertStringContainsString('original', (string) $data['backdrop_path']);
    }

    /**
     * When poster_path or backdrop_path is null, the corresponding URL must be null.
     *
     * Validates: Requirements 2.6, 2.7
     */
    public function test_collection_resource_returns_null_urls_when_paths_are_null(): void
    {
        $model = $this->makeModel([
            'id' => 1,
            'tmdb_id' => 100,
            'name' => 'Test Collection',
            'overview' => null,
            'poster_path' => null,
            'backdrop_path' => null,
        ]);

        $model->setRelation('movies', collect([]));

        $resource = new CollectionResource($model);
        $data = $resource->toArray(new Request);

        $this->assertNull($data['poster_path'], 'poster_path must be null when poster_path is null');
        $this->assertNull($data['backdrop_path'], 'backdrop_path must be null when backdrop_path is null');
    }

    // -------------------------------------------------------------------------
    // Property 5: CollectionMovieResource resolved semantics (Requirements 2.8, 2.9, 2.10)
    // -------------------------------------------------------------------------

    /**
     * When movie_id is null, resolved must be false and no exception is thrown.
     *
     * Validates: Requirements 2.8, 2.9, 2.10
     */
    public function test_collection_movie_resource_resolved_is_false_when_movie_id_is_null(): void
    {
        $model = $this->makeModel([
            'movie_tmdb_id' => 550,
            'movie_id' => null,
        ]);

        $resource = new CollectionMovieResource($model);
        $data = $resource->toArray(new Request);

        $this->assertArrayHasKey('movie_tmdb_id', $data);
        $this->assertArrayHasKey('movie_id', $data);
        $this->assertArrayHasKey('resolved', $data);
        $this->assertNull($data['movie_id']);
        $this->assertFalse($data['resolved'], 'resolved must be false when movie_id is null');
    }

    /**
     * When movie_id is not null, resolved must be true.
     *
     * Validates: Requirements 2.8, 2.9, 2.10
     */
    public function test_collection_movie_resource_resolved_is_true_when_movie_id_is_set(): void
    {
        $model = $this->makeModel([
            'movie_tmdb_id' => 550,
            'movie_id' => 123,
        ]);

        $resource = new CollectionMovieResource($model);
        $data = $resource->toArray(new Request);

        $this->assertSame(123, $data['movie_id']);
        $this->assertTrue($data['resolved'], 'resolved must be true when movie_id is not null');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create an anonymous Eloquent Model instance with the given attributes.
     * No database connection is required.
     */
    private function makeModel(array $attributes): Model
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            public $timestamps = false;
        };

        $model->forceFill($attributes);

        return $model;
    }
}
