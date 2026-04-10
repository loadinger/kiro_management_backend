<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\MovieImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieImageService
{
    public function __construct(
        private readonly MovieImageRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of images for the given movie.
     * Supported filters: image_type (poster|backdrop|logo), page, per_page.
     */
    public function getList(int $movieId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByMovieId($movieId, $filters);
    }
}
