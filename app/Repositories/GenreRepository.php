<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\Contracts\GenreRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GenreRepository extends BaseRepository implements GenreRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Genre);
    }

    /**
     * Paginate genres with optional prefix search on name and exact match on type.
     * Sort whitelist: id, name.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Genre::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', $filters['q'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $allowedSorts = ['id', 'name'];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true) ? $filters['sort'] : 'id';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
