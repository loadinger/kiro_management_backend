<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    // Full mock data returned by DashboardService::getStats()
    private function mockStatsData(): array
    {
        return [
            'entity_counts' => [
                'movies'               => 1050000,
                'tv_shows'             => 210000,
                'persons'              => 5200000,
                'tv_seasons'           => 980000,
                'tv_episodes'          => 20000000,
                'keywords'             => 450000,
                'collections'          => 12000,
                'tv_networks'          => 3500,
                'production_companies' => 180000,
            ],
            'reconcile_rates' => [
                'movie_credits'      => ['total' => 50000000, 'resolved' => 48000000, 'rate' => 0.9600],
                'tv_show_creators'   => ['total' => 120000,   'resolved' => 115000,   'rate' => 0.9583],
                'tv_episode_credits' => ['total' => 80000000, 'resolved' => 75000000, 'rate' => 0.9375],
                'collection_movies'  => ['total' => 60000,    'resolved' => 58000,    'rate' => 0.9667],
            ],
            'translation_coverage' => [
                'departments' => ['total' => 20,     'translated' => 20,     'rate' => 1.0000],
                'jobs'        => ['total' => 3000,   'translated' => 2800,   'rate' => 0.9333],
                'keywords'    => ['total' => 450000, 'translated' => 200000, 'rate' => 0.4444],
                'languages'   => ['total' => 180,    'translated' => 180,    'rate' => 1.0000],
            ],
            'data_freshness' => [
                'movies'      => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false],
                'tv_shows'    => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false],
                'persons'     => ['last_updated_at' => '2024-01-14T06:00:00Z', 'is_stale' => false],
                'tv_seasons'  => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false],
                'tv_episodes' => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false],
                'keywords'    => ['last_updated_at' => null,                   'is_stale' => true],
            ],
            'snapshot_health' => [
                'checked_days'  => 30,
                'healthy_days'  => 28,
                'missing_dates' => ['2024-01-10', '2024-01-11'],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Property 1: 未认证请求被拒绝 (需求 1.1)
    // -------------------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        // Feature: dashboard, Property 1: 未认证请求被拒绝
        $this->getJson('/api/dashboard/stats')
            ->assertStatus(200)
            ->assertJson(['code' => 401, 'message' => '未认证，请先登录', 'data' => null]);
    }

    // -------------------------------------------------------------------------
    // Property 2: Stats 响应包含所有必需顶层字段 (需求 8.1, 8.2)
    // -------------------------------------------------------------------------

    public function test_stats_response_contains_all_top_level_fields(): void
    {
        // Feature: dashboard, Property 2: Stats 响应包含所有必需顶层字段
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)->getJson('/api/dashboard/stats')
            ->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'entity_counts',
                    'reconcile_rates',
                    'translation_coverage',
                    'data_freshness',
                    'snapshot_health',
                ],
            ]);
    }

    // -------------------------------------------------------------------------
    // Property 3: entity_counts 包含 9 个实体且值为非负整数 (需求 2.1, 2.5)
    // -------------------------------------------------------------------------

    public function test_entity_counts_contains_nine_entities_with_non_negative_integers(): void
    {
        // Feature: dashboard, Property 3: entity_counts 包含所有指定实体且值为非负整数
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $entityCounts = $response->json('data.entity_counts');

        $expectedKeys = [
            'movies', 'tv_shows', 'persons', 'tv_seasons', 'tv_episodes',
            'keywords', 'collections', 'tv_networks', 'production_companies',
        ];

        $this->assertCount(9, $entityCounts);

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $entityCounts, "Missing entity key: {$key}");
            $this->assertIsInt($entityCounts[$key], "Value for '{$key}' must be an integer");
            $this->assertGreaterThanOrEqual(0, $entityCounts[$key], "Value for '{$key}' must be non-negative");
        }
    }

    // -------------------------------------------------------------------------
    // Property 4: reconcile_rates 结构完整性 (需求 4.1, 4.3)
    // -------------------------------------------------------------------------

    public function test_reconcile_rates_structure_is_complete(): void
    {
        // Feature: dashboard, Property 4: reconcile_rates 结构完整性
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $reconcileRates = $response->json('data.reconcile_rates');

        $expectedTables = ['movie_credits', 'tv_show_creators', 'tv_episode_credits', 'collection_movies'];

        foreach ($expectedTables as $table) {
            $this->assertArrayHasKey($table, $reconcileRates, "Missing reconcile_rates key: {$table}");

            $entry = $reconcileRates[$table];
            $this->assertArrayHasKey('total', $entry, "{$table} missing 'total'");
            $this->assertArrayHasKey('resolved', $entry, "{$table} missing 'resolved'");
            $this->assertArrayHasKey('rate', $entry, "{$table} missing 'rate'");

            $this->assertIsInt($entry['total'], "{$table}.total must be integer");
            $this->assertIsInt($entry['resolved'], "{$table}.resolved must be integer");
            $this->assertGreaterThanOrEqual(0, $entry['total'], "{$table}.total must be non-negative");
            $this->assertGreaterThanOrEqual(0, $entry['resolved'], "{$table}.resolved must be non-negative");
            $this->assertLessThanOrEqual($entry['total'], $entry['resolved'], "{$table}.resolved must be <= total");
            $this->assertGreaterThanOrEqual(0.0, $entry['rate'], "{$table}.rate must be >= 0.0");
            $this->assertLessThanOrEqual(1.0, $entry['rate'], "{$table}.rate must be <= 1.0");
        }
    }

    // -------------------------------------------------------------------------
    // Property 4: translation_coverage 结构完整性 (需求 5.1, 5.3)
    // -------------------------------------------------------------------------

    public function test_translation_coverage_structure_is_complete(): void
    {
        // Feature: dashboard, Property 4: translation_coverage 结构完整性
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $translationCoverage = $response->json('data.translation_coverage');

        $expectedTables = ['departments', 'jobs', 'keywords', 'languages'];

        foreach ($expectedTables as $table) {
            $this->assertArrayHasKey($table, $translationCoverage, "Missing translation_coverage key: {$table}");

            $entry = $translationCoverage[$table];
            $this->assertArrayHasKey('total', $entry, "{$table} missing 'total'");
            $this->assertArrayHasKey('translated', $entry, "{$table} missing 'translated'");
            $this->assertArrayHasKey('rate', $entry, "{$table} missing 'rate'");

            $this->assertIsInt($entry['total'], "{$table}.total must be integer");
            $this->assertIsInt($entry['translated'], "{$table}.translated must be integer");
            $this->assertGreaterThanOrEqual(0, $entry['total'], "{$table}.total must be non-negative");
            $this->assertGreaterThanOrEqual(0, $entry['translated'], "{$table}.translated must be non-negative");
            $this->assertLessThanOrEqual($entry['total'], $entry['translated'], "{$table}.translated must be <= total");
            $this->assertGreaterThanOrEqual(0.0, $entry['rate'], "{$table}.rate must be >= 0.0");
            $this->assertLessThanOrEqual(1.0, $entry['rate'], "{$table}.rate must be <= 1.0");
        }
    }

    // -------------------------------------------------------------------------
    // Property 6: data_freshness 结构完整性 (需求 6.1, 6.3, 6.5)
    // -------------------------------------------------------------------------

    public function test_data_freshness_structure_is_complete(): void
    {
        // Feature: dashboard, Property 6: data_freshness 结构完整性
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $dataFreshness = $response->json('data.data_freshness');

        $expectedTables = ['movies', 'tv_shows', 'persons', 'tv_seasons', 'tv_episodes', 'keywords'];

        foreach ($expectedTables as $table) {
            $this->assertArrayHasKey($table, $dataFreshness, "Missing data_freshness key: {$table}");

            $entry = $dataFreshness[$table];
            $this->assertArrayHasKey('last_updated_at', $entry, "{$table} missing 'last_updated_at'");
            $this->assertArrayHasKey('is_stale', $entry, "{$table} missing 'is_stale'");

            // last_updated_at must be a string (ISO 8601) or null
            $this->assertTrue(
                is_string($entry['last_updated_at']) || is_null($entry['last_updated_at']),
                "{$table}.last_updated_at must be a string or null"
            );
            $this->assertIsBool($entry['is_stale'], "{$table}.is_stale must be boolean");
        }

        // keywords has null last_updated_at and is_stale = true in mock data
        $this->assertNull($dataFreshness['keywords']['last_updated_at']);
        $this->assertTrue($dataFreshness['keywords']['is_stale']);
    }

    // -------------------------------------------------------------------------
    // Property 8: snapshot_health 结构完整性 (需求 7.1, 7.4)
    // -------------------------------------------------------------------------

    public function test_snapshot_health_structure_is_complete(): void
    {
        // Feature: dashboard, Property 8: snapshot_health 结构与 missing_dates 计算正确性
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn($this->mockStatsData());
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)->assertJson(['code' => 0]);

        $snapshotHealth = $response->json('data.snapshot_health');

        $this->assertArrayHasKey('checked_days', $snapshotHealth);
        $this->assertArrayHasKey('healthy_days', $snapshotHealth);
        $this->assertArrayHasKey('missing_dates', $snapshotHealth);

        $this->assertSame(30, $snapshotHealth['checked_days']);
        $this->assertIsInt($snapshotHealth['healthy_days']);
        $this->assertGreaterThanOrEqual(0, $snapshotHealth['healthy_days']);
        $this->assertLessThanOrEqual(30, $snapshotHealth['healthy_days']);
        $this->assertIsArray($snapshotHealth['missing_dates']);

        // Verify missing_dates are in ascending order
        $missingDates = $snapshotHealth['missing_dates'];
        $sorted = $missingDates;
        sort($sorted);
        $this->assertSame($sorted, $missingDates, 'missing_dates must be in ascending order');

        // Verify healthy_days + count(missing_dates) = checked_days
        $this->assertSame(
            $snapshotHealth['checked_days'],
            $snapshotHealth['healthy_days'] + count($missingDates),
            'healthy_days + count(missing_dates) must equal checked_days'
        );
    }

    // -------------------------------------------------------------------------
    // Property 11: 子项失败不影响其他子项 (需求 8.4)
    // -------------------------------------------------------------------------

    public function test_failed_subquery_returns_null_for_that_field_only(): void
    {
        // Feature: dashboard, Property 11: 子项失败不影响其他子项
        $this->mock(DashboardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getStats')->once()->andReturn([
                'entity_counts'        => null, // simulates a failed sub-query
                'reconcile_rates'      => [
                    'movie_credits' => ['total' => 100, 'resolved' => 90, 'rate' => 0.9000],
                ],
                'translation_coverage' => [
                    'departments' => ['total' => 20, 'translated' => 20, 'rate' => 1.0000],
                ],
                'data_freshness'       => [
                    'movies' => ['last_updated_at' => '2024-01-15T08:30:00Z', 'is_stale' => false],
                ],
                'snapshot_health'      => [
                    'checked_days'  => 30,
                    'healthy_days'  => 30,
                    'missing_dates' => [],
                ],
            ]);
        });

        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonPath('data.entity_counts', null)
            ->assertJsonPath('data.reconcile_rates.movie_credits.total', 100)
            ->assertJsonPath('data.data_freshness.movies.is_stale', false)
            ->assertJsonPath('data.snapshot_health.checked_days', 30);

        // Rate is returned as a number; verify it equals 1 (float/int comparison)
        $this->assertEquals(1.0, $response->json('data.translation_coverage.departments.rate'));
    }
}
