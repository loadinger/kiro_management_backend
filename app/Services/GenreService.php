<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\GenreRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class GenreService
{
    public function __construct(
        private readonly GenreRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }
}
