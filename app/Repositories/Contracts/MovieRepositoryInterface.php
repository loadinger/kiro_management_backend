<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Movie;
use Illuminate\Pagination\LengthAwarePaginator;

interface MovieRepositoryInterface
{
    /**
     * Paginate movies with optional filters.
     * Supported filters: q, genre_id, status, release_year, adult, sort, order, page, per_page.
     * Sort whitelist: popularity, release_date, vote_average, vote_count, id. Default: id DESC.
     * Large table constraint: page max 1000 (enforced by FormRequest).
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    /**
     * Find a movie by its local id. Returns null when not found.
     */
    public function findById(int $id): ?Movie;
}
