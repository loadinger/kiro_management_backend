<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowGenreRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowGenreService
{
    public function __construct(
        private readonly TvShowGenreRepositoryInterface $repository
    ) {}

    /**
     * Return all genres associated with the given tv show.
     */
    public function getList(int $tvShowId): Collection
    {
        return $this->repository->getByTvShowId($tvShowId);
    }
}
