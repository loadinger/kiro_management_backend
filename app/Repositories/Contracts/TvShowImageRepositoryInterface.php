<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface TvShowImageRepositoryInterface
{
    /**
     * Paginate images for a given tv show.
     * tv_show_id is required to prevent full-table scans.
     * Supported filters: image_type (poster|backdrop|logo), page, per_page.
     */
    public function paginateByTvShowId(int $tvShowId, array $filters): LengthAwarePaginator;
}
