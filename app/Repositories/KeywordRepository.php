<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Keyword;
use App\Repositories\Contracts\KeywordRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordRepository extends BaseRepository implements KeywordRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Keyword);
    }

    /**
     * Paginate keywords with optional prefix search on name.
     * Sort whitelist: id.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Keyword::query();

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

    /** Return all keywords without pagination. Only suitable for small tables. */
    public function getAll(array $filters): Collection
    {
        $query = Keyword::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy('id', $order)->get();
    }
}
