<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TvShow;
use Illuminate\Pagination\LengthAwarePaginator;

interface TvShowRepositoryInterface
{
    /**
     * Paginate tv shows with optional filters.
     * Supported filters: q, genre_id, status, first_air_year, in_production, sort, order, page, per_page.
     * Sort whitelist: popularity, first_air_date, vote_average, vote_count, id. Default: id DESC.
     * Large table constraint: page max 1000 (enforced by FormRequest).
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    /**
     * Find a tv show by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvShow;
}
