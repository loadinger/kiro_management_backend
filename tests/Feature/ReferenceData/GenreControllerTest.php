<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Models\User;
use App\Services\GenreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    // Feature: reference-data, Property 1: unauthenticated request returns 401
    // Validates: Requirements 1.1, 1.2
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/genres');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Feature: reference-data, Property 9: genres type filter returns matching records
    // Validates: Requirements 6.2
    public function test_genre_type_filter_returns_matching_records(): void
    {
        $items = [
            (object) ['id' => 1, 'tmdb_id' => 28, 'name' => 'Action', 'type' => 'movie'],
            (object) ['id' => 2, 'tmdb_id' => 12, 'name' => 'Adventure', 'type' => 'movie'],
        ];

        $this->mock(GenreService::class, function (MockInterface $mock) use ($items) {
            $mock->shouldReceive('getList')
                ->once()
                ->with(\Mockery::on(fn ($filters) => ($filters['type'] ?? null) === 'movie'))
                ->andReturn(new LengthAwarePaginator($items, 2, 20, 1));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/genres?type=movie');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // Feature: reference-data, Property 10: invalid genre type returns 422
    // Validates: Requirements 6.3
    public function test_invalid_genre_type_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/genres?type=anime');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 4: per_page exceeding 100 returns 422
    // Validates: Requirements 6.5
    public function test_per_page_exceeding_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/genres?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 5: page exceeding 1000 returns 422
    // Validates: Requirements 6.5
    public function test_page_exceeding_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/genres?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 7: q exceeding 100 chars returns 422
    // Validates: Requirements 6.5
    public function test_q_exceeding_100_chars_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $longQ = str_repeat('a', 101);
        $response = $this->withToken($token)->getJson('/api/genres?q='.$longQ);

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 8: invalid sort field returns 422
    // Validates: Requirements 6.6
    public function test_invalid_sort_field_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/genres?sort=invalid_field');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }
}
