<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\MovieProductionCompanyRepositoryInterface;
use Illuminate\Support\Collection;

class MovieProductionCompanyService
{
    public function __construct(
        private readonly MovieProductionCompanyRepositoryInterface $repository
    ) {}

    /**
     * Return all production companies associated with the given movie.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return $this->repository->getByMovieId($movieId);
    }
}
