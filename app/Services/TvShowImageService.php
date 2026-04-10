<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TvShowImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvShowImageService
{
    public function __construct(
        private readonly TvShowImageRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of images for the given tv show.
     * Supported filters: image_type (poster|backdrop|logo), page, per_page.
     */
    public function getList(int $tvShowId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByTvShowId($tvShowId, $filters);
    }
}
