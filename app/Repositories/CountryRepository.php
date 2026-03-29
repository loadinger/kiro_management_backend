<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Country);
    }

    /**
     * Paginate countries with optional prefix search on english_name or native_name.
     * Sort whitelist: id, english_name.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Country::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('english_name', 'like', $q.'%')
                    ->orWhere('native_name', 'like', $q.'%');
            });
        }

        $allowedSorts = ['id', 'english_name'];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true) ? $filters['sort'] : 'id';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
