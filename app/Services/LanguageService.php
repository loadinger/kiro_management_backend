<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class LanguageService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $repository
    ) {}

    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }
}
