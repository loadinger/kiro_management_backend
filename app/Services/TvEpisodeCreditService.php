<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvEpisodeCreditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeCreditService
{
    public function __construct(
        private readonly TvEpisodeCreditRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of credits for the given tv episode.
     * Large table constraint (extremely large): tv_episode_id is required.
     * Records with null person_id (async reconciliation pending) are included.
     * Supported filters: credit_type (cast|crew), page, per_page.
     */
    public function getList(int $tvEpisodeId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByTvEpisodeId($tvEpisodeId, $filters);
    }
}
