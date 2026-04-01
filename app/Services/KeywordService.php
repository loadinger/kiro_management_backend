<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\KeywordRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class KeywordService
{
    public function __construct(
        private readonly KeywordRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    public function getAll(array $filters): Collection
    {
        return $this->repository->getAll($filters);
    }
}
