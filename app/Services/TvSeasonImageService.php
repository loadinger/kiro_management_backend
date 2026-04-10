<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvSeasonImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvSeasonImageService
{
    public function __construct(
        private readonly TvSeasonImageRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of images for the given tv season.
     * Supported filters: page, per_page.
     */
    public function getList(int $tvSeasonId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByTvSeasonId($tvSeasonId, $filters);
    }
}
