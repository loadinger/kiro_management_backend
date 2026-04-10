<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\MovieKeywordRepositoryInterface;
use Illuminate\Support\Collection;

class MovieKeywordService
{
    public function __construct(
        private readonly MovieKeywordRepositoryInterface $repository
    ) {}

    /**
     * Return all keywords associated with the given movie.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return $this->repository->getByMovieId($movieId);
    }
}
