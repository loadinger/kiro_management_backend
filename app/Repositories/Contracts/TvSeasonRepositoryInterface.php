<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TvSeason;
use Illuminate\Database\Eloquent\Collection;

interface TvSeasonRepositoryInterface
{
    /**
     * Return all seasons for a given tv show.
     * tv_show_id is required to prevent full-table scans (100万+ rows).
     * Sort whitelist: season_number, air_date, vote_average, id. Default: season_number ASC.
     */
    public function getAllByTvShowId(int $tvShowId, array $filters): Collection;

    /**
     * Find a tv season by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvSeason;
}
