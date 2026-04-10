<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TvEpisodeCreditRepositoryInterface
{
    /**
     * Paginate credits for a given episode.
     * tv_episode_id is required to prevent full-table scans (extremely large table).
     * Eagerly loads the person relation (person_id may be NULL due to async reconciliation).
     * Supported filters: credit_type (cast|crew), page, per_page.
     */
    public function paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator;
}
