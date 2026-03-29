<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\CountryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryService
{
    public function __construct(
        private readonly CountryRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }
}
