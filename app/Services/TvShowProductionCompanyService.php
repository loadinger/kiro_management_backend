<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowProductionCompanyRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowProductionCompanyService
{
    public function __construct(
        private readonly TvShowProductionCompanyRepositoryInterface $repository
    ) {}

    /**
     * Return all production companies associated with the given tv show.
     */
    public function getList(int $tvShowId): Collection
    {
        return $this->repository->getByTvShowId($tvShowId);
    }
}
