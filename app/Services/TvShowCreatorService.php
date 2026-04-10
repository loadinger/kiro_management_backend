<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowCreatorRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowCreatorService
{
    public function __construct(
        private readonly TvShowCreatorRepositoryInterface $repository
    ) {}

    /**
     * Return all creators associated with the given tv show.
     * Records with null person_id (async reconciliation pending) are included.
     */
    public function getList(int $tvShowId): Collection
    {
        return $this->repository->getByTvShowId($tvShowId);
    }
}
