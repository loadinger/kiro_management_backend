<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvNetwork;
use App\Repositories\Contracts\TvNetworkRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvNetworkRepository extends BaseRepository implements TvNetworkRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvNetwork);
    }

    /**
     * Paginate TV networks with optional prefix search on name.
     * Sort whitelist: id, name.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = TvNetwork::query();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where('name', 'like', $q.'%');
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

    public function findById(int $id): ?TvNetwork
    {
        return TvNetwork::find($id);
    }
}
