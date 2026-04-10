<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowNetworkRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowNetworkService
{
    public function __construct(
        private readonly TvShowNetworkRepositoryInterface $repository
    ) {}

    /**
     * Return all tv networks associated with the given tv show.
     */
    public function getList(int $tvShowId): Collection
    {
        return $this->repository->getByTvShowId($tvShowId);
    }
}
