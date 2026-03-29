<?php

declare(strict_types=1);

namespace Tests\Feature\ReferenceData;

use App\Models\User;
use App\Services\JobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    // Feature: reference-data, Property 1: unauthenticated request returns 401
    // Validates: Requirements 1.1, 1.2
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/jobs');

        $response->assertStatus(200)
            ->assertJson(['code' => 401, 'data' => null]);
    }

    // Feature: reference-data, Property 11: jobs department_id filter returns matching records
    // Validates: Requirements 7.2
    public function test_job_department_id_filter_returns_matching_records(): void
    {
        $items = [
            (object) ['id' => 1, 'name' => 'Director', 'department_id' => 5],
            (object) ['id' => 2, 'name' => 'Producer', 'department_id' => 5],
        ];

        $this->mock(JobService::class, function (MockInterface $mock) use ($items) {
            $mock->shouldReceive('getList')
                ->once()
                ->with(\Mockery::on(fn ($filters) => ($filters['department_id'] ?? null) == 5))
                ->andReturn(new LengthAwarePaginator($items, 2, 20, 1));
        });

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?department_id=5');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    // Feature: reference-data, Property: non-positive department_id returns 422
    // Validates: Requirements 7.3
    public function test_non_positive_department_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?department_id=0');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property: non-integer department_id returns 422
    // Validates: Requirements 7.3
    public function test_non_integer_department_id_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?department_id=abc');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 4: per_page exceeding 100 returns 422
    // Validates: Requirements 7.5
    public function test_per_page_exceeding_100_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?per_page=101');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 5: page exceeding 1000 returns 422
    // Validates: Requirements 7.5
    public function test_page_exceeding_1000_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?page=1001');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 7: q exceeding 100 chars returns 422
    // Validates: Requirements 7.5
    public function test_q_exceeding_100_chars_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $longQ = str_repeat('a', 101);
        $response = $this->withToken($token)->getJson('/api/jobs?q=' . $longQ);

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }

    // Feature: reference-data, Property 8: invalid sort field returns 422
    // Validates: Requirements 7.6
    public function test_invalid_sort_field_returns_422(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/jobs?sort=invalid_field');

        $response->assertStatus(200)
            ->assertJson(['code' => 422, 'data' => null]);
    }
}
