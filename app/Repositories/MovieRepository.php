<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Movie;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieRepository extends BaseRepository implements MovieRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['popularity', 'release_date', 'vote_average', 'vote_count', 'id'];

    public function __construct()
    {
        parent::__construct(new Movie);
    }

    /**
     * Paginate movies with optional filters.
     * q: prefix match on title and original_title (LIKE q%).
     * genre_id: JOIN movie_genres to filter by genre.
     * status: exact match on status field.
     * release_year: YEAR(release_date) = ?.
     * adult: exact match on adult field.
     * sort/order: whitelist-validated, default id DESC.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Movie::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q): void {
                $sub->where('title', 'like', $q.'%')
                    ->orWhere('original_title', 'like', $q.'%');
            });
        }

        if (! empty($filters['genre_id'])) {
            $query->join('movie_genres', 'movie_genres.movie_id', '=', 'movies.id')
                ->where('movie_genres.genre_id', (int) $filters['genre_id'])
                ->select('movies.*');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['release_year'])) {
            $query->whereYear('release_date', (int) $filters['release_year']);
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
     * Find a movie by its local id with collection eagerly loaded. Returns null when not found.
     */
    public function findById(int $id): ?Movie
    {
        return Movie::with('collection')->find($id);
    }
}
