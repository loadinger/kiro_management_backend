<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TvEpisode;
use Illuminate\Pagination\LengthAwarePaginator;

interface TvEpisodeRepositoryInterface
{
    /**
     * Paginate episodes for a given tv season.
     * tv_season_id is required to prevent full-table scans (2000万+ rows).
     * Supported filters: sort, order, page, per_page.
     * Sort whitelist: episode_number, air_date, vote_average, id. Default: id ASC.
     */
    public function paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator;

    /**
     * Find a tv episode by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvEpisode;
}
