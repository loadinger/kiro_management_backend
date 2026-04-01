<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CountryRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function getAll(array $filters): Collection;
}
