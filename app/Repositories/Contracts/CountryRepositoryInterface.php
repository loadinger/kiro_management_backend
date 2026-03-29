<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface CountryRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;
}
