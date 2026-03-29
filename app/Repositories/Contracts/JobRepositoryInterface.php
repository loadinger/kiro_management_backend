<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface JobRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;
}
