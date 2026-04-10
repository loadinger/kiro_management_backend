<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvEpisodeImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeImageService
{
    public function __construct(
        private readonly TvEpisodeImageRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of images for the given tv episode.
     * Supported filters: page, per_page.
     */
    public function getList(int $tvEpisodeId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByTvEpisodeId($tvEpisodeId, $filters);
    }
}
