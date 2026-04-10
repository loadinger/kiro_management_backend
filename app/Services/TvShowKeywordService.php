<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowKeywordRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowKeywordService
{
    public function __construct(
        private readonly TvShowKeywordRepositoryInterface $repository
    ) {}

    /**
     * Return all keywords associated with the given tv show.
     */
    public function getList(int $tvShowId): Collection
    {
        return $this->repository->getByTvShowId($tvShowId);
    }
}
