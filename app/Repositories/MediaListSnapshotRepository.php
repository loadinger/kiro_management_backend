<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ListType;
use App\Models\MediaListSnapshot;
use App\Repositories\Contracts\MediaListSnapshotRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MediaListSnapshotRepository extends BaseRepository implements MediaListSnapshotRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new MediaListSnapshot);
    }

    /**
     * Find the maximum snapshot_date for the given list type.
     * Returns null when no data exists for that list type.
     */
    public function findLatestDate(ListType $listType): ?string
    {
        $result = MediaListSnapshot::query()
            ->where('list_type', $listType->value)
            ->max('snapshot_date');

        return $result !== null ? (string) $result : null;
    }

    /**
     * Find snapshots for the given list type and date, ordered by rank ASC.
     * If snapshotDate is null, findLatestDate is called first to resolve the target date.
     * Queries via the (list_type, snapshot_date, rank) composite index.
     */
    public function findByListType(ListType $listType, ?string $snapshotDate): Collection
    {
        $targetDate = $snapshotDate ?? $this->findLatestDate($listType);

        if ($targetDate === null) {
            return collect();
        }

        return MediaListSnapshot::query()
            ->where('list_type', $listType->value)
            ->where('snapshot_date', $targetDate)
            ->orderBy('rank')
            ->get();
    }

    /**
     * Find movie snapshots joined with movies table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded automatically by the INNER JOIN.
     * Queries via the (list_type, snapshot_date, rank) composite index on snapshots
     * and the primary key index on movies.
     *
     * @return array{rows: Collection, snapshot_date: string|null}
     */
    public function findMovieListWithEntities(ListType $listType, ?string $snapshotDate): array
    {
        $targetDate = $snapshotDate ?? $this->findLatestDate($listType);

        if ($targetDate === null) {
            return ['rows' => collect(), 'snapshot_date' => null];
        }

        $rows = DB::table('media_list_snapshots as s')
            ->join('movies as m', 'm.id', '=', 's.local_id')
            ->where('s.list_type', $listType->value)
            ->where('s.snapshot_date', $targetDate)
            ->orderBy('s.rank')
            ->select([
                's.rank',
                's.popularity',
                's.snapshot_date',
                's.tmdb_id',
                's.local_id',
                'm.id',
                'm.title',
                'm.original_title',
                'm.release_date',
                'm.poster_path',
                'm.vote_average',
                'm.status',
            ])
            ->get();

        return ['rows' => $rows, 'snapshot_date' => $targetDate];
    }

    /**
     * Find TV show snapshots joined with tv_shows table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded automatically by the INNER JOIN.
     * Queries via the (list_type, snapshot_date, rank) composite index on snapshots
     * and the primary key index on tv_shows.
     *
     * @return array{rows: Collection, snapshot_date: string|null}
     */
    public function findTvShowListWithEntities(ListType $listType, ?string $snapshotDate): array
    {
        $targetDate = $snapshotDate ?? $this->findLatestDate($listType);

        if ($targetDate === null) {
            return ['rows' => collect(), 'snapshot_date' => null];
        }

        $rows = DB::table('media_list_snapshots as s')
            ->join('tv_shows as t', 't.id', '=', 's.local_id')
            ->where('s.list_type', $listType->value)
            ->where('s.snapshot_date', $targetDate)
            ->orderBy('s.rank')
            ->select([
                's.rank',
                's.popularity',
                's.snapshot_date',
                's.tmdb_id',
                's.local_id',
                't.id',
                't.name',
                't.original_name',
                't.first_air_date',
                't.poster_path',
                't.vote_average',
                't.status',
            ])
            ->get();

        return ['rows' => $rows, 'snapshot_date' => $targetDate];
    }

    /**
     * Find person snapshots joined with persons table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded automatically by the INNER JOIN.
     * Queries via the (list_type, snapshot_date, rank) composite index on snapshots
     * and the primary key index on persons.
     *
     * @return array{rows: Collection, snapshot_date: string|null}
     */
    public function findPersonListWithEntities(ListType $listType, ?string $snapshotDate): array
    {
        $targetDate = $snapshotDate ?? $this->findLatestDate($listType);

        if ($targetDate === null) {
            return ['rows' => collect(), 'snapshot_date' => null];
        }

        $rows = DB::table('media_list_snapshots as s')
            ->join('persons as p', 'p.id', '=', 's.local_id')
            ->where('s.list_type', $listType->value)
            ->where('s.snapshot_date', $targetDate)
            ->orderBy('s.rank')
            ->select([
                's.rank',
                's.popularity',
                's.snapshot_date',
                's.tmdb_id',
                's.local_id',
                'p.id',
                'p.name',
                'p.known_for_department',
                'p.profile_path',
                'p.gender',
            ])
            ->get();

        return ['rows' => $rows, 'snapshot_date' => $targetDate];
    }
}
