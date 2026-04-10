<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TvEpisodeImageRepositoryInterface
{
    /**
     * Paginate images for a given tv episode.
     * tv_episode_id is required to prevent full-table scans.
     * Supported filters: page, per_page.
     */
    public function paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator;
}
