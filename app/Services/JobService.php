<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class JobService
{
    public function __construct(
        private readonly JobRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }
}
