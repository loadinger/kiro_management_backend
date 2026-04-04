<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    /**
     * Get the total row count for each main entity table.
     *
     * @return array{
     *     movies: int,
     *     tv_shows: int,
     *     persons: int,
     *     tv_seasons: int,
     *     tv_episodes: int,
     *     keywords: int,
     *     collections: int,
     *     tv_networks: int,
     *     production_companies: int
     * }
     */
    public function getEntityCounts(): array;

    /**
     * Get total and resolved counts for each async-reconcile relation table.
     *
     * "resolved" means the foreign key (person_id or movie_id) has been filled in.
     *
     * @return array{
     *     movie_credits: array{total: int, resolved: int},
     *     tv_show_creators: array{total: int, resolved: int},
     *     tv_episode_credits: array{total: int, resolved: int},
     *     collection_movies: array{total: int, resolved: int}
     * }
     */
    public function getReconcileRates(): array;

    /**
     * Get total and translated counts for each reference data table.
     *
     * "translated" means the translated_at column is NOT NULL.
     *
     * @return array{
     *     departments: array{total: int, translated: int},
     *     jobs: array{total: int, translated: int},
     *     keywords: array{total: int, translated: int},
     *     languages: array{total: int, translated: int}
     * }
     */
    public function getTranslationCoverage(): array;

    /**
     * Get the MAX(updated_at) timestamp for each main entity table.
     *
     * Returns null for a given table when the table is empty.
     *
     * @return array{
     *     movies: string|null,
     *     tv_shows: string|null,
     *     persons: string|null,
     *     tv_seasons: string|null,
     *     tv_episodes: string|null,
     *     keywords: string|null
     * }
     */
    public function getDataFreshness(): array;

    /**
     * Get the distinct snapshot_date values from media_list_snapshots
     * that fall within the last $days days.
     *
     * Uses the (list_type, snapshot_date, rank) index to avoid full table scans.
     *
     * @param  int    $days  Number of days to look back (e.g. 30)
     * @return array<int, string>  Array of date strings in 'Y-m-d' format
     */
    public function getSnapshotDates(int $days): array;

    /**
     * Get raw daily new-record counts grouped by DATE(created_at) for each
     * requested entity within the last $days days.
     *
     * For the 'persons' table a WHERE created_at >= NOW() - INTERVAL $days DAY
     * condition is always applied to leverage the created_at index.
     *
     * @param  int           $days      Number of days to look back
     * @param  array<string> $entities  Entity names to query, e.g. ['movies', 'tv_shows', 'persons']
     * @return array<string, array<int, array{date: string, count: int}>>
     *         Keyed by entity name; each value is an array of ['date' => 'Y-m-d', 'count' => int] rows
     */
    public function getTrendRows(int $days, array $entities): array;
}
