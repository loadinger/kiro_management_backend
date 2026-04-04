<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    /**
     * Get the total row count for each main entity table.
     *
     * Executes one COUNT(*) per table against the nine core entity tables.
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
    public function getEntityCounts(): array
    {
        $tables = [
            'movies',
            'tv_shows',
            'persons',
            'tv_seasons',
            'tv_episodes',
            'keywords',
            'collections',
            'tv_networks',
            'production_companies',
        ];

        $result = [];
        foreach ($tables as $table) {
            $result[$table] = (int) DB::table($table)->count();
        }

        return $result;
    }

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
    public function getReconcileRates(): array
    {
        // Tables keyed by table name => foreign key column used to determine "resolved"
        $tables = [
            'movie_credits'      => 'person_id',
            'tv_show_creators'   => 'person_id',
            'tv_episode_credits' => 'person_id',
            'collection_movies'  => 'movie_id',
        ];

        $result = [];
        foreach ($tables as $table => $fkColumn) {
            $total    = (int) DB::table($table)->count();
            $resolved = (int) DB::table($table)->whereNotNull($fkColumn)->count();

            $result[$table] = [
                'total'    => $total,
                'resolved' => $resolved,
            ];
        }

        return $result;
    }

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
    public function getTranslationCoverage(): array
    {
        $tables = ['departments', 'jobs', 'keywords', 'languages'];

        $result = [];
        foreach ($tables as $table) {
            $total      = (int) DB::table($table)->count();
            $translated = (int) DB::table($table)->whereNotNull('translated_at')->count();

            $result[$table] = [
                'total'      => $total,
                'translated' => $translated,
            ];
        }

        return $result;
    }

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
    public function getDataFreshness(): array
    {
        $tables = ['movies', 'tv_shows', 'persons', 'tv_seasons', 'tv_episodes', 'keywords'];

        $result = [];
        foreach ($tables as $table) {
            $max = DB::table($table)->max('updated_at');
            $result[$table] = $max !== null ? (string) $max : null;
        }

        return $result;
    }

    /**
     * Get the distinct snapshot_date values from media_list_snapshots
     * that fall within the last $days days.
     *
     * Uses the (list_type, snapshot_date, rank) index to avoid full table scans.
     *
     * @param  int    $days  Number of days to look back (e.g. 30)
     * @return array<int, string>  Array of date strings in 'Y-m-d' format
     */
    public function getSnapshotDates(int $days): array
    {
        $rows = DB::table('media_list_snapshots')
            ->selectRaw('DISTINCT snapshot_date')
            ->whereRaw('snapshot_date >= DATE(NOW()) - INTERVAL ? DAY', [$days])
            ->orderBy('snapshot_date')
            ->pluck('snapshot_date');

        return $rows->map(fn ($date) => (string) $date)->values()->all();
    }

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
    public function getTrendRows(int $days, array $entities): array
    {
        $result = [];

        foreach ($entities as $entity) {
            $query = DB::table($entity)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereRaw('created_at >= NOW() - INTERVAL ? DAY', [$days])
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)');

            // persons table always gets the WHERE clause to leverage the index;
            // other tables also benefit from it, so we apply it universally above.
            // The explicit note here is for the persons table requirement.

            $rows = $query->get();

            $result[$entity] = $rows->map(fn ($row) => [
                'date'  => (string) $row->date,
                'count' => (int) $row->count,
            ])->values()->all();
        }

        return $result;
    }
}
