<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface MovieImageRepositoryInterface
{
    /**
     * Paginate images for a given movie.
     * movie_id is required to prevent full-table scans.
     * Supported filters: image_type (poster|backdrop|logo), page, per_page.
     */
    public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator;
}
