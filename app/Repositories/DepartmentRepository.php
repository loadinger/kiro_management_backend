<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Department);
    }

    /**
     * Paginate departments with optional contains search on name.
     * Sort whitelist: id.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Department::query();

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

    /** Return all departments without pagination. Only suitable for small tables. */
    public function getAll(array $filters): Collection
    {
        $query = Department::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy('id', $order)->get();
    }
}
