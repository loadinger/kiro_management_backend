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
 * Property-based tests for DashboardService.
 *
 * Each test method loops 100 times over randomly generated inputs to verify
 * that the service's core computation properties hold for all valid inputs.
 *
 * No database is used; DashboardRepositoryInterface is mocked in every case.
 */
class DashboardPropertyTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Property 5: rate calculation correctness (Requirements 4.2, 5.2)
    // -------------------------------------------------------------------------

    /**
     * Property 5: rate calculation correctness.
     *
     * For any non-negative integer total and value (0 ≤ value ≤ total):
     *   - total = 0  → rate = 1.0
     *   - total > 0  → rate = round(value / total, 4)
     *   - rate is always in [0.0, 1.0]
     *
     * Verified via reconcile_rates and translation_coverage paths.
     *
     * Requirements 4.2, 5.2
     */
    public function test_rate_calculation_is_correct(): void
    {
        // Feature: dashboard, Property 5: rate 计算正确性
        for ($i = 0; $i < 100; $i++) {
            Cache::flush();

            $total = random_int(0, 10_000_000);
            $value = $total === 0 ? 0 : random_int(0, $total);

            $repo = Mockery::mock(DashboardRepositoryInterface::class);
            $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
            $repo->shouldReceive('getReconcileRates')->once()->andReturn([
                'movie_credits' => ['total' => $total, 'resolved' => $value],
            ]);
            $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([
                'departments' => ['total' => $total, 'translated' => $value],
            ]);
            $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
            $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);

            Log::shouldReceive('error')->zeroOrMoreTimes();

            $service = new DashboardService($repo);
            $stats = $service->getStats();

            $reconcileRate = $stats['reconcile_rates']['movie_credits']['rate'];
            $translationRate = $stats['translation_coverage']['departments']['rate'];

            if ($total === 0) {
                $this->assertSame(1.0, $reconcileRate,
                    "Iteration $i: reconcile rate must be 1.0 when total=0");
                $this->assertSame(1.0, $translationRate,
                    "Iteration $i: translation rate must be 1.0 when total=0");
            } else {
                $expected = round($value / $total, 4);
                $this->assertSame($expected, $reconcileRate,
                    "Iteration $i: reconcile rate mismatch (total=$total, value=$value)");
                $this->assertSame($expected, $translationRate,
                    "Iteration $i: translation rate mismatch (total=$total, value=$value)");
            }

            // Rate must always be in [0.0, 1.0]
            $this->assertGreaterThanOrEqual(0.0, $reconcileRate,
                "Iteration $i: reconcile rate must be >= 0.0");
            $this->assertLessThanOrEqual(1.0, $reconcileRate,
                "Iteration $i: reconcile rate must be <= 1.0");

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 7: is_stale calculation correctness (Requirement 6.4)
    // -------------------------------------------------------------------------

    /**
     * Property 7: is_stale calculation correctness.
     *
     * For any last_updated_at value (including null):
     *   - null              → is_stale = true
     *   - diff > 48 hours   → is_stale = true
     *   - diff ≤ 48 hours   → is_stale = false
     *
     * Requirement 6.4
     */
    public function test_is_stale_calculation_is_correct(): void
    {
        // Feature: dashboard, Property 7: is_stale 计算正确性
        for ($i = 0; $i < 100; $i++) {
            Cache::flush();

            // Randomly decide whether to use null or a timestamp
            $useNull = random_int(0, 9) === 0; // ~10% null cases
            // Avoid hoursAgo=48 boundary to prevent race conditions in test execution
            do {
                $hoursAgo = random_int(0, 200);
            } while ($hoursAgo === 48);

            if ($useNull) {
                $lastUpdatedAt = null;
                $expectedStale = true;
            } else {
                $lastUpdatedAt = Carbon::now()->subHours($hoursAgo)->toDateTimeString();
                // Strictly greater than 48 hours → stale
                $expectedStale = $hoursAgo > 48;
            }

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
            $stats = $service->getStats();

            $isStale = $stats['data_freshness']['movies']['is_stale'];

            $this->assertSame(
                $expectedStale,
                $isStale,
                "Iteration $i: is_stale mismatch (hoursAgo=$hoursAgo, useNull=$useNull)"
            );

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 8: missing_dates calculation correctness (Requirements 7.3, 7.4)
    // -------------------------------------------------------------------------

    /**
     * Property 8: missing_dates calculation correctness.
     *
     * For any random subset of the last 30 days as present_dates:
     *   - missing_dates = full 30-day sequence − present_dates
     *   - missing_dates is in ascending order
     *   - healthy_days + count(missing_dates) = 30
     *
     * Requirements 7.3, 7.4
     */
    public function test_missing_dates_calculation_is_correct(): void
    {
        // Feature: dashboard, Property 8: missing_dates 计算正确性
        for ($i = 0; $i < 100; $i++) {
            Cache::flush();

            // Build the full 30-day sequence (oldest → newest)
            $fullSequence = [];
            for ($d = 29; $d >= 0; $d--) {
                $fullSequence[] = Carbon::today()->subDays($d)->format('Y-m-d');
            }

            // Pick a random subset as "present" dates
            $presentCount = random_int(0, 30);
            $shuffled = $fullSequence;
            shuffle($shuffled);
            $presentDates = array_slice($shuffled, 0, $presentCount);

            $repo = Mockery::mock(DashboardRepositoryInterface::class);
            $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
            $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
            $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
            $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
            $repo->shouldReceive('getSnapshotDates')->once()->andReturn($presentDates);

            Log::shouldReceive('error')->zeroOrMoreTimes();

            $service = new DashboardService($repo);
            $stats = $service->getStats();

            $health = $stats['snapshot_health'];

            // Compute expected missing dates
            $presentSet = array_flip($presentDates);
            $expectedMissing = array_values(
                array_filter($fullSequence, fn (string $d) => ! isset($presentSet[$d]))
            );
            sort($expectedMissing);

            $this->assertSame(30, $health['checked_days'],
                "Iteration $i: checked_days must always be 30");

            $this->assertSame($expectedMissing, $health['missing_dates'],
                "Iteration $i: missing_dates mismatch (presentCount=$presentCount)");

            // healthy_days + missing = 30
            $this->assertSame(
                30,
                $health['healthy_days'] + count($health['missing_dates']),
                "Iteration $i: healthy_days + missing_dates count must equal 30"
            );

            // missing_dates must be ascending
            $sorted = $health['missing_dates'];
            sort($sorted);
            $this->assertSame($sorted, $health['missing_dates'],
                "Iteration $i: missing_dates must be in ascending order");

            Mockery::close();
        }
    }

    // -------------------------------------------------------------------------
    // Property 10: trends series fill (Requirements 3.5, 8.3)
    // -------------------------------------------------------------------------

    /**
     * Property 10: trends series fill.
     *
     * For any valid days value (7, 30, 90) and sparse data rows:
     *   - dates array length equals days
     *   - each entity's series array length equals dates length
     *   - dates with no data are filled with 0
     *
     * Requirements 3.5, 8.3
     */
    public function test_trend_series_fills_zero_for_missing_dates(): void
    {
        // Feature: dashboard, Property 10: trends series 填充
        $allowedDays = [7, 30, 90];

        for ($i = 0; $i < 100; $i++) {
            Cache::flush();

            $days = $allowedDays[array_rand($allowedDays)];
            $entities = ['movies', 'tv_shows', 'persons'];

            // Build the full date sequence for this iteration
            $fullDates = [];
            for ($d = $days - 1; $d >= 0; $d--) {
                $fullDates[] = Carbon::today()->subDays($d)->format('Y-m-d');
            }

            // Generate sparse rows: pick a random subset of dates for each entity
            $rows = [];
            foreach ($entities as $entity) {
                $rows[$entity] = [];
                foreach ($fullDates as $date) {
                    if (random_int(0, 1) === 1) {
                        $rows[$entity][] = [
                            'date' => $date,
                            'count' => random_int(0, 1000),
                        ];
                    }
                }
            }

            $repo = Mockery::mock(DashboardRepositoryInterface::class);
            $repo->shouldReceive('getTrendRows')->once()->andReturn($rows);

            Log::shouldReceive('error')->zeroOrMoreTimes();

            $service = new DashboardService($repo);
            $result = $service->getTrends($days, $entities);

            // dates length must equal days
            $this->assertCount($days, $result['dates'],
                "Iteration $i: dates length must equal days=$days");

            // dates must be in ascending order and match the expected sequence
            $this->assertSame($fullDates, $result['dates'],
                "Iteration $i: dates sequence mismatch");

            foreach ($entities as $entity) {
                // series length must equal dates length
                $this->assertCount($days, $result['series'][$entity],
                    "Iteration $i: series[$entity] length must equal days=$days");

                // Build expected series (0 for missing dates)
                $indexed = [];
                foreach ($rows[$entity] as $row) {
                    $indexed[$row['date']] = $row['count'];
                }

                foreach ($result['dates'] as $idx => $date) {
                    $expected = $indexed[$date] ?? 0;
                    $this->assertSame($expected, $result['series'][$entity][$idx],
                        "Iteration $i: series[$entity][$idx] (date=$date) expected $expected");
                }
            }

            Mockery::close();
        }
    }
}
