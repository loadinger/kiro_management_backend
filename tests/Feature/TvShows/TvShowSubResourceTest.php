<?php

declare(strict_types=1);

namespace Tests\Feature\TvShows;

use App\Models\TvShowCreator;
use App\Models\User;
use App\Services\TvShowCreatorService;
use App\Services\TvShowGenreService;
use App\Services\TvShowImageService;
use App\Services\TvShowKeywordService;
use App\Services\TvShowNetworkService;
use App\Services\TvShowProductionCompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class TvShowSubResourceTest extends TestCase
{
    use RefreshDatabase;

    // --- Genres ---

    public function test_missing_tv_show_id_for_genres_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-genres')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_genres_returns_listing(): void
    {
        $this->mock(TvShowGenreService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')->once()->andReturn(new Collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-genres?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // --- Keywords ---

    public function test_missing_tv_show_id_for_keywords_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-keywords')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_keywords_returns_listing(): void
    {
        $this->mock(TvShowKeywordService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')->once()->andReturn(new Collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-keywords?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // --- Networks ---

    public function test_missing_tv_show_id_for_networks_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-networks')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_networks_returns_listing(): void
    {
        $this->mock(TvShowNetworkService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')->once()->andReturn(new Collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-networks?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // --- Production Companies ---

    public function test_missing_tv_show_id_for_production_companies_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-production-companies')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_production_companies_returns_listing(): void
    {
        $this->mock(TvShowProductionCompanyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')->once()->andReturn(new Collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-production-companies?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // --- Images ---

    public function test_missing_tv_show_id_for_images_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-images')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_invalid_image_type_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-images?tv_show_id=1&image_type=invalid')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_images_returns_paginated_list(): void
    {
        $this->mock(TvShowImageService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 0, 20));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-images?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => [
                    'list',
                    'pagination' => ['total', 'page', 'per_page', 'last_page'],
                ],
            ]);
    }

    // --- Creators ---

    public function test_missing_tv_show_id_for_creators_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-creators')
            ->assertStatus(200)
            ->assertJson(['code' => 422]);
    }

    public function test_creators_returns_listing(): void
    {
        $this->mock(TvShowCreatorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getList')->once()->andReturn(new Collection);
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/tv-show-creators?tv_show_id=1')
            ->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    public function test_creator_with_null_person_id_has_null_person_field_and_is_not_filtered(): void
    {
        // A creator with person_id = null (async reconciliation pending)
        $creator = (new TvShowCreator)->forceFill([
            'tv_show_id' => 1,
            'person_tmdb_id' => 999,
            'person_id' => null,
        ]);

        $this->mock(TvShowCreatorService::class, function (MockInterface $mock) use ($creator) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(new Collection([$creator]));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/tv-show-creators?tv_show_id=1');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            // Record is not filtered out — list has exactly 1 item
            ->assertJsonCount(1, 'data')
            // person field is null for unresolved async association
            ->assertJsonPath('data.0.person', null)
            ->assertJsonPath('data.0.person_id', null)
            ->assertJsonPath('data.0.person_tmdb_id', 999);
    }
}
