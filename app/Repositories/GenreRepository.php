<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\Contracts\GenreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class GenreRepository extends BaseRepository implements GenreRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Genre);
    }

    /**
     * Paginate genres with optional contains search on name and exact match on type.
     * Sort whitelist: id.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Genre::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy('id', $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Return all genres without pagination, applying the same filters and sort as paginateWithFilters.
     * Only suitable for small tables.
     */
    public function getAll(array $filters): Collection
    {
        $query = Genre::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy('id', $order)->get();
    }
}
