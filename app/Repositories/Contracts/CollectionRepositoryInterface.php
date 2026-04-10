<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CollectionRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function findByIdWithMovies(int $id): ?Collection;
}
