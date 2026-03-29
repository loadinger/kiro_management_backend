<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TvNetwork;
use Illuminate\Pagination\LengthAwarePaginator;

interface TvNetworkRepositoryInterface
{
    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function findById(int $id): ?TvNetwork;
}
