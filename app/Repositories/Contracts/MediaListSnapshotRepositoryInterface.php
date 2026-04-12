<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ListType;
use Illuminate\Support\Collection;

interface MediaListSnapshotRepositoryInterface
{
    /**
     * Find snapshots for the given list type and date, ordered by rank ASC.
     * If snapshotDate is null, the latest available date is used automatically.
     * Queries via the (list_type, snapshot_date, rank) composite index.
     */
    public function findByListType(ListType $listType, ?string $snapshotDate): Collection;

    /**
     * Find the maximum snapshot_date for the given list type.
     * Returns null when no data exists for that list type.
     */
    public function findLatestDate(ListType $listType): ?string;

    /**
     * Find movie snapshots joined with movies table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded.
     * Returns array{rows: Collection, snapshot_date: string|null}.
     */
    public function findMovieListWithEntities(ListType $listType, ?string $snapshotDate): array;

    /**
     * Find TV show snapshots joined with tv_shows table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded.
     * Returns array{rows: Collection, snapshot_date: string|null}.
     */
    public function findTvShowListWithEntities(ListType $listType, ?string $snapshotDate): array;

    /**
     * Find person snapshots joined with persons table via local_id (INNER JOIN).
     * Snapshots with null local_id are excluded.
     * Returns array{rows: Collection, snapshot_date: string|null}.
     */
    public function findPersonListWithEntities(ListType $listType, ?string $snapshotDate): array;
}
