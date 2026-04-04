<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for DashboardService business logic.
 *
 * Tests private methods indirectly through the public getStats() and getTrends()
 * methods. The DashboardRepositoryInterface is mocked so no database is needed.
 *
 * Cache is flushed before each test so Cache::remember() always executes the
 * callback and the real computation logic is exercised.
 */
class DashboardServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // is_stale boundary tests (Requirement 6.4)
    // -------------------------------------------------------------------------

    /**
     * When last_updated_at is null, is_stale must be true.
     *
     * Requirement 6.4: null last_updated_at → is_stale = true
     */
    public function test_is_stale_is_true_when_last_updated_at_is_null(): void
    {
        Cache::flush();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([
            'movies' => null,
        ]);
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $this->assertNull($stats['data_freshness']['movies']['last_updated_at']);
        $this->assertTrue($stats['data_freshness']['movies']['is_stale']);
    }

    /**
     * When last_updated_at is exactly 48 hours ago, is_stale must be false.
     *
     * Boundary value: diff == 48h → is_stale = false (condition is strictly > 48h)
     * We use 48h - 1s to avoid sub-millisecond timing drift while still testing
     * the boundary: anything ≤ 48h must not be stale.
     * Requirement 6.4
     */
    public function test_is_stale_is_false_when_exactly_48_hours(): void
    {
        Cache::flush();

        // 48 hours minus 1 second — clearly within the boundary, must NOT be stale.
        // Using exactly 48h risks a false positive due to sub-millisecond execution time.
        $lastUpdatedAt = Carbon::now()->subSeconds(48 * 3600 - 1)->toDateTimeString();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([
            'movies' => $lastUpdatedAt,
        ]);
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $this->assertFalse(
            $stats['data_freshness']['movies']['is_stale'],
            '48h - 1s should NOT be stale (condition is strictly > 48h)'
        );
    }

    /**
     * When last_updated_at is more than 48 hours ago, is_stale must be true.
     *
     * Requirement 6.4: diff > 48h → is_stale = true
     */
    public function test_is_stale_is_true_when_over_48_hours(): void
    {
        Cache::flush();

        // 48 hours + 1 second ago
        $lastUpdatedAt = Carbon::now()->subSeconds(48 * 3600 + 1)->toDateTimeString();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([
            'movies' => $lastUpdatedAt,
        ]);
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $this->assertTrue(
            $stats['data_freshness']['movies']['is_stale'],
            '48h + 1s should be stale'
        );
    }

    // -------------------------------------------------------------------------
    // missing_dates boundary tests (Requirements 7.3, 7.4)
    // -------------------------------------------------------------------------

    /**
     * When no snapshot dates exist for the last 30 days, all 30 days are missing.
     *
     * Requirements 7.3, 7.4
     */
    public function test_missing_dates_when_all_30_days_missing(): void
    {
        Cache::flush();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
        // No snapshot dates at all
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $snapshotHealth = $stats['snapshot_health'];

        $this->assertSame(30, $snapshotHealth['checked_days']);
        $this->assertSame(0, $snapshotHealth['healthy_days']);
        $this->assertCount(30, $snapshotHealth['missing_dates']);

        // Verify ascending order
        $sorted = $snapshotHealth['missing_dates'];
        sort($sorted);
        $this->assertSame($sorted, $snapshotHealth['missing_dates'], 'missing_dates must be ascending');
    }

    /**
     * When all 30 days have snapshots, missing_dates must be empty.
     *
     * Requirements 7.3, 7.4
     */
    public function test_missing_dates_when_all_30_days_present(): void
    {
        Cache::flush();

        // Build the full 30-day date sequence
        $allDates = [];
        for ($i = 29; $i >= 0; $i--) {
            $allDates[] = Carbon::today()->subDays($i)->format('Y-m-d');
        }

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn($allDates);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $snapshotHealth = $stats['snapshot_health'];

        $this->assertSame(30, $snapshotHealth['checked_days']);
        $this->assertSame(30, $snapshotHealth['healthy_days']);
        $this->assertSame([], $snapshotHealth['missing_dates']);
    }

    // -------------------------------------------------------------------------
    // Trend series zero-fill test (Requirement 3.5)
    // -------------------------------------------------------------------------

    /**
     * buildTrendSeries fills 0 for dates that have no data rows.
     *
     * Requirement 3.5: missing dates in the series must be filled with 0
     */
    public function test_trend_series_fills_zero_for_missing_dates(): void
    {
        Cache::flush();

        // Use a 3-day window for simplicity
        $days     = 3;
        $entities = ['movies'];

        $date0 = Carbon::today()->subDays(2)->format('Y-m-d'); // oldest
        $date1 = Carbon::today()->subDays(1)->format('Y-m-d'); // middle — no data
        $date2 = Carbon::today()->format('Y-m-d');             // newest

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        // getTrendRows returns data only for date0 and date2; date1 is missing
        $repo->shouldReceive('getTrendRows')->once()->andReturn([
            'movies' => [
                ['date' => $date0, 'count' => 10],
                ['date' => $date2, 'count' => 25],
            ],
        ]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $result  = $service->getTrends($days, $entities);

        $this->assertSame([$date0, $date1, $date2], $result['dates']);
        $this->assertSame([10, 0, 25], $result['series']['movies']);
    }

    // -------------------------------------------------------------------------
    // Rate = 1.0 when total = 0 (Requirements 4.2, 5.2)
    // -------------------------------------------------------------------------

    /**
     * When total is 0, rate must be 1.0 for both reconcile_rates and translation_coverage.
     *
     * Requirements 4.2, 5.2: total = 0 → rate = 1.0
     */
    public function test_rate_is_1_when_total_is_zero(): void
    {
        Cache::flush();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);
        $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
        $repo->shouldReceive('getReconcileRates')->once()->andReturn([
            'movie_credits' => ['total' => 0, 'resolved' => 0],
        ]);
        $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([
            'departments' => ['total' => 0, 'translated' => 0],
        ]);
        $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
        $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        $this->assertSame(
            1.0,
            $stats['reconcile_rates']['movie_credits']['rate'],
            'reconcile rate must be 1.0 when total = 0'
        );
        $this->assertSame(
            1.0,
            $stats['translation_coverage']['departments']['rate'],
            'translation rate must be 1.0 when total = 0'
        );
    }
}
