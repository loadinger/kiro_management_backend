<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TvSeasonImageRepositoryInterface
{
    /**
     * Paginate images for a given tv season.
     * tv_season_id is required to prevent full-table scans.
     * Supported filters: page, per_page.
     */
    public function paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator;
}
