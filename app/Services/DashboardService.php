<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository,
    ) {}

    /**
     * Aggregate all dashboard statistics with Redis caching (TTL 10 minutes).
     *
     * Each sub-item is fetched independently; a failure in one sub-item
     * returns null for that field only and does not affect the others.
     *
     * @return array{
     *     entity_counts: array|null,
     *     reconcile_rates: array|null,
     *     translation_coverage: array|null,
     *     data_freshness: array|null,
     *     snapshot_health: array|null
     * }
     */
    public function getStats(): array
    {
        return Cache::remember('dashboard:stats', 600, function (): array {
            $entityCounts = null;
            try {
                $entityCounts = $this->repository->getEntityCounts();
            } catch (\Throwable $e) {
                Log::error('Dashboard entity_counts query failed', ['error' => $e->getMessage()]);
            }

            $reconcileRates = null;
            try {
                $rows = $this->repository->getReconcileRates();
                $reconcileRates = [];
                foreach ($rows as $table => $data) {
                    $reconcileRates[$table] = [
                        'total' => $data['total'],
                        'resolved' => $data['resolved'],
                        'rate' => $this->computeRate($data['total'], $data['resolved']),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Dashboard reconcile_rates query failed', ['error' => $e->getMessage()]);
            }

            $translationCoverage = null;
            try {
                $rows = $this->repository->getTranslationCoverage();
                $translationCoverage = [];
                foreach ($rows as $table => $data) {
                    $translationCoverage[$table] = [
                        'total' => $data['total'],
                        'translated' => $data['translated'],
                        'rate' => $this->computeRate($data['total'], $data['translated']),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Dashboard translation_coverage query failed', ['error' => $e->getMessage()]);
            }

            $dataFreshness = null;
            try {
                $freshnessRows = $this->repository->getDataFreshness();
                $dataFreshness = $this->computeStaleStatus($freshnessRows);
            } catch (\Throwable $e) {
                Log::error('Dashboard data_freshness query failed', ['error' => $e->getMessage()]);
            }

            $snapshotHealth = null;
            try {
                $days = 30;
                $presentDates = $this->repository->getSnapshotDates($days);
                $snapshotHealth = $this->computeSnapshotHealth($presentDates, $days);
            } catch (\Throwable $e) {
                Log::error('Dashboard snapshot_health query failed', ['error' => $e->getMessage()]);
            }

            return [
                'entity_counts' => $entityCounts,
                'reconcile_rates' => $reconcileRates,
                'translation_coverage' => $translationCoverage,
                'data_freshness' => $dataFreshness,
                'snapshot_health' => $snapshotHealth,
            ];
        });
    }

    /**
     * Return daily new-record trend data for the requested entities and day range.
     *
     * Cache key includes both $days and the sorted entity list so that
     * different orderings of the same entities hit the same cache entry.
     * TTL is 5 minutes.
     *
     * @param  array<string>  $entities
     * @return array{dates: array<string>, series: array<string, array<int>>}
     */
    public function getTrends(int $days, array $entities): array
    {
        $sortedEntities = $entities;
        sort($sortedEntities);
        $cacheKey = 'dashboard:trends:'.$days.':'.implode(',', $sortedEntities);

        return Cache::remember($cacheKey, 300, function () use ($days, $entities): array {
            // Build the full date sequence for the requested range (oldest → newest).
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates[] = Carbon::today()->subDays($i)->format('Y-m-d');
            }

            $rows = $this->repository->getTrendRows($days, $entities);

            return [
                'dates' => $dates,
                'series' => $this->buildTrendSeries($rows, $dates, $entities),
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Calculate the rate (resolved or translated / total), rounded to 4 decimal places.
     *
     * Returns 1.0 when total is 0 to avoid division-by-zero and signal full coverage.
     */
    private function computeRate(int $total, int $value): float
    {
        if ($total === 0) {
            return 1.0;
        }

        return round($value / $total, 4);
    }

    /**
     * Enrich raw MAX(updated_at) rows with an is_stale flag.
     *
     * A table is considered stale when its last_updated_at is null
     * or more than 48 hours in the past.
     *
     * @param  array<string, string|null>  $freshnessRows  Keyed by table name, value is timestamp string or null
     * @return array<string, array{last_updated_at: string|null, is_stale: bool}>
     */
    private function computeStaleStatus(array $freshnessRows): array
    {
        $now = Carbon::now();
        $result = [];

        foreach ($freshnessRows as $table => $lastUpdatedAt) {
            if ($lastUpdatedAt === null) {
                $result[$table] = [
                    'last_updated_at' => null,
                    'is_stale' => true,
                ];

                continue;
            }

            $updatedAt = Carbon::parse($lastUpdatedAt);
            $isStale = $now->diffInSeconds($updatedAt, absolute: true) > (48 * 3600);

            $result[$table] = [
                'last_updated_at' => $updatedAt->toIso8601ZuluString(),
                'is_stale' => $isStale,
            ];
        }

        return $result;
    }

    /**
     * Compare the set of dates that have snapshots against the full date sequence
     * for the last $days days and produce the snapshot health summary.
     *
     * @param  array<string>  $presentDates  Dates that have at least one snapshot ('Y-m-d')
     * @return array{checked_days: int, healthy_days: int, missing_dates: array<string>}
     */
    private function computeSnapshotHealth(array $presentDates, int $days): array
    {
        $fullSequence = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $fullSequence[] = Carbon::today()->subDays($i)->format('Y-m-d');
        }

        $presentSet = array_flip($presentDates);
        $missingDates = array_values(
            array_filter($fullSequence, fn (string $date) => ! isset($presentSet[$date]))
        );

        // missing_dates must be in ascending order (fullSequence is already ascending).
        sort($missingDates);

        return [
            'checked_days' => $days,
            'healthy_days' => $days - count($missingDates),
            'missing_dates' => $missingDates,
        ];
    }

    /**
     * Fill sparse repository rows into equal-length arrays aligned to $dates.
     *
     * Missing dates are filled with 0.
     *
     * @param  array<string, array<int, array{date: string, count: int}>>  $rows
     * @param  array<string>  $dates
     * @param  array<string>  $entities
     * @return array<string, array<int>>
     */
    private function buildTrendSeries(array $rows, array $dates, array $entities): array
    {
        $series = [];

        foreach ($entities as $entity) {
            // Index the raw rows by date for O(1) lookup.
            $indexed = [];
            foreach ($rows[$entity] ?? [] as $row) {
                $indexed[$row['date']] = $row['count'];
            }

            $series[$entity] = array_map(
                fn (string $date) => $indexed[$date] ?? 0,
                $dates
            );
        }

        return $series;
    }
}
