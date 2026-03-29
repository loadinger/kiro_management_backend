<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ProductionCompany;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductionCompanyRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function findById(int $id): ?ProductionCompany;
}
