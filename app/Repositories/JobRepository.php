<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Job;
use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class JobRepository extends BaseRepository implements JobRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Job);
    }

    /**
     * Paginate jobs with optional prefix search on name and exact match on department_id.
     * Sort whitelist: id, name, department_id.
     */
    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Job::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', $filters['q'].'%');
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        $allowedSorts = ['id', 'name', 'department_id'];
        $sort = in_array($filters['sort'] ?? null, $allowedSorts, true) ? $filters['sort'] : 'id';
        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
