<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Collection;
use App\Repositories\Contracts\CollectionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CollectionRepository extends BaseRepository implements CollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Collection);
    }

    /**
     * Paginate collections with optional fuzzy search on name.
     * Sort whitelist: id (asc/desc).
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Collection::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy('id', $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Find a collection by id with its movies eagerly loaded.
     * Left joins the movies table so basic movie fields are available even when movie_id is null.
     * Returns null when not found.
     */
    public function findByIdWithMovies(int $id): ?Collection
    {
        return Collection::with(['movies' => function ($query): void {
            $query->leftJoin('movies', 'collection_movies.movie_id', '=', 'movies.id')
                ->select(
                    'collection_movies.collection_id',
                    'collection_movies.movie_tmdb_id',
                    'collection_movies.movie_id',
                    'movies.title',
                    'movies.original_title',
                    'movies.poster_path as movie_poster_path',
                    'movies.release_date',
                    'movies.vote_average',
                );
        }])->find($id);
    }
}
