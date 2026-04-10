<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvShow;
use App\Repositories\Contracts\TvShowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvShowRepository extends BaseRepository implements TvShowRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['popularity', 'first_air_date', 'vote_average', 'vote_count', 'id'];

    public function __construct()
    {
        parent::__construct(new TvShow);
    }

    /**
     * Paginate tv shows with optional filters.
     * q: prefix match on name and original_name (LIKE q%).
     * genre_id: JOIN tv_show_genres to filter by genre.
     * status: exact match on status field.
     * first_air_year: YEAR(first_air_date) = ?.
     * in_production: exact match on in_production boolean field.
     * adult: exact match on adult boolean field.
     * sort/order: whitelist-validated, default id DESC.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = TvShow::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', $q.'%')
                    ->orWhere('original_name', 'like', $q.'%');
            });
        }

        if (! empty($filters['genre_id'])) {
            $query->join('tv_show_genres', 'tv_show_genres.tv_show_id', '=', 'tv_shows.id')
                ->where('tv_show_genres.genre_id', (int) $filters['genre_id'])
                ->select('tv_shows.*');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['first_air_year'])) {
            $query->whereYear('first_air_date', (int) $filters['first_air_year']);
        }

        if (isset($filters['in_production']) && $filters['in_production'] !== '') {
            $query->where('in_production', (bool) $filters['in_production']);
        }

        if (isset($filters['adult']) && $filters['adult'] !== '') {
            $query->where('adult', (bool) $filters['adult']);
        }

        $sort = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS, true)
            ? $filters['sort']
            : 'id';

        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Find a tv show by its local id. Returns null when not found.
     */
    public function findById(int $id): ?TvShow
    {
        return TvShow::find($id);
    }
}
