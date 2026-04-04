<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Property-based tests for DashboardService rate calculation.
 *
 * Each test runs 100 iterations with random inputs to verify the invariants
 * hold across the full input space, without requiring eris/eris.
 *
 * Feature: dashboard, Property 5: rate calculation correctness
 * Validates: requirements 4.2, 5.2
 */
class DashboardRatePropertyTest extends TestCase
{
    /**
     * Property 5: rate is always in [0.0, 1.0] for any non-negative total and value.
     *
     * Invariants:
     *   - total = 0  → rate = 1.0
     *   - total > 0  → rate = round(value / total, 4)
     *   - result ∈ [0.0, 1.0] always
     */
    public function test_rate_is_always_between_0_and_1(): void
    {
        // Feature: dashboard, Property 5: rate calculation correctness
        for ($i = 0; $i < 100; $i++) {
            $total = random_int(0, 10_000_000);
            $value = $total === 0 ? 0 : random_int(0, $total);

            $rate = $this->computeRateViaService($total, $value, 'reconcile');

            $this->assertGreaterThanOrEqual(
                0.0,
                $rate,
                "rate must be >= 0.0 (total={$total}, value={$value})"
            );
            $this->assertLessThanOrEqual(
                1.0,
                $rate,
                "rate must be <= 1.0 (total={$total}, value={$value})"
            );
        }
    }

    /**
     * Property 5: when total = 0, rate must be exactly 1.0 (full coverage by convention).
     */
    public function test_rate_is_1_when_total_is_zero(): void
    {
        // Feature: dashboard, Property 5: rate calculation correctness
        $rate = $this->computeRateViaService(0, 0, 'reconcile');

        $this->assertSame(1.0, $rate);
    }

    /**
     * Property 5: when total > 0, rate = round(value / total, 4).
     */
    public function test_rate_equals_rounded_division_when_total_is_positive(): void
    {
        // Feature: dashboard, Property 5: rate calculation correctness
        for ($i = 0; $i < 100; $i++) {
            $total = random_int(1, 10_000_000);
            $value = random_int(0, $total);

            $expected = round($value / $total, 4);
            $actual   = $this->computeRateViaService($total, $value, 'reconcile');

            $this->assertSame(
                $expected,
                $actual,
                "rate mismatch (total={$total}, value={$value})"
            );
        }
    }

    /**
     * Property 5: same invariants hold for translation_coverage (translated / total).
     */
    public function test_translation_rate_invariants_hold(): void
    {
        // Feature: dashboard, Property 5: rate calculation correctness
        for ($i = 0; $i < 100; $i++) {
            $total = random_int(0, 10_000_000);
            $value = $total === 0 ? 0 : random_int(0, $total);

            $rate = $this->computeRateViaService($total, $value, 'translation');

            if ($total === 0) {
                $this->assertSame(1.0, $rate, "rate must be 1.0 when total=0");
            } else {
                $this->assertSame(
                    round($value / $total, 4),
                    $rate,
                    "rate mismatch (total={$total}, value={$value})"
                );
            }

            $this->assertGreaterThanOrEqual(0.0, $rate);
            $this->assertLessThanOrEqual(1.0, $rate);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Drive DashboardService::getStats() with a mocked repository that returns
     * a single row for the requested sub-item, then extract the computed rate.
     *
     * @param  string $type  'reconcile' or 'translation'
     */
    private function computeRateViaService(int $total, int $value, string $type): float
    {
        Cache::flush();

        $repo = Mockery::mock(DashboardRepositoryInterface::class);

        if ($type === 'reconcile') {
            $repo->shouldReceive('getReconcileRates')->once()->andReturn([
                'movie_credits' => ['total' => $total, 'resolved' => $value],
            ]);
            $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
            $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([]);
            $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
            $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);
        } else {
            $repo->shouldReceive('getTranslationCoverage')->once()->andReturn([
                'departments' => ['total' => $total, 'translated' => $value],
            ]);
            $repo->shouldReceive('getEntityCounts')->once()->andReturn([]);
            $repo->shouldReceive('getReconcileRates')->once()->andReturn([]);
            $repo->shouldReceive('getDataFreshness')->once()->andReturn([]);
            $repo->shouldReceive('getSnapshotDates')->once()->andReturn([]);
        }

        Log::shouldReceive('error')->zeroOrMoreTimes();

        $service = new DashboardService($repo);
        $stats   = $service->getStats();

        if ($type === 'reconcile') {
            return $stats['reconcile_rates']['movie_credits']['rate'];
        }

        return $stats['translation_coverage']['departments']['rate'];
    }
}
