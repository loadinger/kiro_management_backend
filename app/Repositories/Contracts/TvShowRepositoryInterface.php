<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TvShow;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    /**
     * Find tv shows by a list of local ids. Returns a Collection keyed by id.
     *
     * @param  array<int>  $ids
     * @return Collection<int, TvShow>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Find tv shows by a list of TMDB ids. Returns a Collection keyed by tmdb_id.
     *
     * @param  array<int>  $tmdbIds
     * @return Collection<int, TvShow>
     */
    public function findByTmdbIds(array $tmdbIds): Collection;
}
