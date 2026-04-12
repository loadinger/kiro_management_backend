<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListType;
use App\Repositories\Contracts\MediaListSnapshotRepositoryInterface;

class MediaListSnapshotService
{
    public function __construct(
        private readonly MediaListSnapshotRepositoryInterface $snapshotRepository,
    ) {}

    /**
     * Get a movie list snapshot for the given list type and optional date.
     * Snapshots without a local_id (not yet entity-refreshed) are excluded by the INNER JOIN.
     *
     * @return array{list: array<int, array<string, mixed>>, snapshot_date: string|null}
     */
    public function getMovieList(ListType $listType, ?string $snapshotDate): array
    {
        $result = $this->snapshotRepository->findMovieListWithEntities($listType, $snapshotDate);
        $rows = $result['rows'];
        $resolvedDate = $result['snapshot_date'];

        $list = $rows->map(fn (object $row): array => [
            'rank' => $row->rank,
            'popularity' => $row->popularity,
            'snapshot_date' => $row->snapshot_date,
            'tmdb_id' => $row->tmdb_id,
            'local_id' => $row->local_id,
            'id' => $row->id,
            'title' => $row->title,
            'original_title' => $row->original_title,
            'release_date' => $row->release_date,
            'poster_path' => $row->poster_path,
            'vote_average' => $row->vote_average,
            'status' => $row->status,
        ])->all();

        return ['list' => $list, 'snapshot_date' => $resolvedDate];
    }

    /**
     * Get a TV show list snapshot for the given list type and optional date.
     * Snapshots without a local_id (not yet entity-refreshed) are excluded by the INNER JOIN.
     *
     * @return array{list: array<int, array<string, mixed>>, snapshot_date: string|null}
     */
    public function getTvShowList(ListType $listType, ?string $snapshotDate): array
    {
        $result = $this->snapshotRepository->findTvShowListWithEntities($listType, $snapshotDate);
        $rows = $result['rows'];
        $resolvedDate = $result['snapshot_date'];

        $list = $rows->map(fn (object $row): array => [
            'rank' => $row->rank,
            'popularity' => $row->popularity,
            'snapshot_date' => $row->snapshot_date,
            'tmdb_id' => $row->tmdb_id,
            'local_id' => $row->local_id,
            'id' => $row->id,
            'name' => $row->name,
            'original_name' => $row->original_name,
            'first_air_date' => $row->first_air_date,
            'poster_path' => $row->poster_path,
            'vote_average' => $row->vote_average,
            'status' => $row->status,
        ])->all();

        return ['list' => $list, 'snapshot_date' => $resolvedDate];
    }

    /**
     * Get a person list snapshot for the given list type and optional date.
     * Snapshots without a local_id (not yet entity-refreshed) are excluded by the INNER JOIN.
     *
     * @return array{list: array<int, array<string, mixed>>, snapshot_date: string|null}
     */
    public function getPersonList(ListType $listType, ?string $snapshotDate): array
    {
        $result = $this->snapshotRepository->findPersonListWithEntities($listType, $snapshotDate);
        $rows = $result['rows'];
        $resolvedDate = $result['snapshot_date'];

        $list = $rows->map(fn (object $row): array => [
            'rank' => $row->rank,
            'popularity' => $row->popularity,
            'snapshot_date' => $row->snapshot_date,
            'tmdb_id' => $row->tmdb_id,
            'local_id' => $row->local_id,
            'id' => $row->id,
            'name' => $row->name,
            'known_for_department' => $row->known_for_department,
            'profile_path' => $row->profile_path,
            'gender' => $row->gender,
        ])->all();

        return ['list' => $list, 'snapshot_date' => $resolvedDate];
    }
}
