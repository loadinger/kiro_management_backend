<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface LanguageRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;
}
