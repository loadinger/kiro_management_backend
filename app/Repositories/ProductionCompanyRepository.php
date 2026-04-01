<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductionCompany;
use App\Repositories\Contracts\ProductionCompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductionCompanyRepository extends BaseRepository implements ProductionCompanyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ProductionCompany);
    }

    /**
     * Paginate production companies with optional prefix search on name.
     * Sort whitelist: id.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = ProductionCompany::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where('name', 'like', '%'.$q.'%');
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy('id', $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    public function findById(int $id): ?ProductionCompany
    {
        return ProductionCompany::find($id);
    }
}
