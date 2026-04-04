<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardTrendsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a mock trends response for the given days and entities.
     *
     * @param  int           $days
     * @param  array<string> $entities
     * @return array{dates: array<string>, series: array<string, array<int>>}
     */
    private function mockTrendsData(int $days = 30, array $entities = ['movies', 'tv_shows', 'persons']): array
    {
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }

        $series = [];
        foreach ($entities as $entity) {
            $series[$entity] = array_fill(0, $days, 0);
        }

        return ['dates' => $dates, 'series' => $series];
    }

    // -------------------------------------------------------------------------
    // Property 1: 未认证请求被拒绝 (需求 1.2)
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        // Feature: dashboard, Property 1: 未认证请求被拒绝
        $this->getJson('/api/dashboard/trends')
            ->assertStatus(200)
            ->assertJson(['code' => 401, 'message' => '未认证，请先登录', 'data' => null]);
    }

    // -------------------------------------------------------------------------
    // Property 9: trends 非法参数返回 422 (需求 3.3)
    // -------------------------------------------------------------------------

    public function test_invalid_days_parameter_returns_422(): void
    {
        // Feature: dashboard, Property 9: trends 非法参数返回 422
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/dashboard/trends?days=999')
            ->assertStatus(200)
            ->assertJson([
                'code'    => 422,
                'message' => '参数错误：days 只允许 7、30 或 90',
                'data'    => null,
            ]);
    }

    // -------------------------------------------------------------------------
    // Property 9: trends 非法参数返回 422 (需求 3.4)
    // -------------------------------------------------------------------------

    public function test_invalid_entities_parameter_returns_422(): void
    {
        // Feature: dashboard, Property 9: trends 非法参数返回 422
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/dashboard/trends?entities=movies,invalid_entity')
            ->assertStatus(200)
            ->assertJson([
                'code'    => 422,
                'message' => '参数错误：entities 包含不支持的实体类型',
                'data'    => null,
            ]);
    }

    // -------------------------------------------------------------------------
    // Property 10: trends dates 数组长度等于 days (需求 3.5, 8.3)
    // -------------------------------------------------------------------------

    public function test_dates_array_length_equals_days(): void
    {
        // Feature: dashboard, Property 10: trends 响应 dates 与 series 等长
        $days = 7;

        $this->mock(DashboardService::class, function (MockInterface $mock) use ($days) {
            $mock->shouldReceive('getTrends')
                ->once()
                ->andReturn($this->mockTrendsData($days, ['movies', 'tv_shows', 'persons']));
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson("/api/dashboard/trends?days={$days}");

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $dates = $response->json('data.dates');

        $this->assertIsArray($dates);
        $this->assertCount($days, $dates, "dates array length must equal days={$days}");
    }

    // -------------------------------------------------------------------------
    // Property 10: series 中每个实体数组长度与 dates 相同 (需求 3.5, 8.3)
    // -------------------------------------------------------------------------

    public function test_series_arrays_have_same_length_as_dates(): void
    {
        // Feature: dashboard, Property 10: trends 响应 dates 与 series 等长
        $days     = 30;
        $entities = ['movies', 'tv_shows', 'persons'];

        $this->mock(DashboardService::class, function (MockInterface $mock) use ($days, $entities) {
            $mock->shouldReceive('getTrends')
                ->once()
                ->andReturn($this->mockTrendsData($days, $entities));
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson("/api/dashboard/trends?days={$days}");

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $dates  = $response->json('data.dates');
        $series = $response->json('data.series');

        $this->assertIsArray($dates);
        $this->assertIsArray($series);

        foreach ($entities as $entity) {
            $this->assertArrayHasKey($entity, $series, "series must contain key: {$entity}");
            $this->assertCount(
                count($dates),
                $series[$entity],
                "series[{$entity}] length must equal dates length"
            );
        }
    }

    // -------------------------------------------------------------------------
    // 默认参数应用 (需求 3.1, 3.2)
    // -------------------------------------------------------------------------

    public function test_default_parameters_are_applied_when_omitted(): void
    {
        // Feature: dashboard, 需求 3.1 & 3.2: 默认参数 days=30, entities=movies,tv_shows,persons
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTrends')
                ->once()
                ->with(30, ['movies', 'tv_shows', 'persons'])
                ->andReturn($this->mockTrendsData(30, ['movies', 'tv_shows', 'persons']));
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/trends');

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'dates',
                    'series' => ['movies', 'tv_shows', 'persons'],
                ],
            ]);

        $this->assertCount(30, $response->json('data.dates'));
    }
}
