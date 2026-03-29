<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Keyword;
use App\Repositories\Contracts\KeywordRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordRepository extends BaseRepository implements KeywordRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Keyword);
    }

    /**
     * Paginate keywords with optional prefix search on name.
     * Sort whitelist: id, name.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Keyword::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', $filters['q'].'%');
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
