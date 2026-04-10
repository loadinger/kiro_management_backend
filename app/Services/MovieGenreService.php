<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\MovieGenreRepositoryInterface;
use Illuminate\Support\Collection;

class MovieGenreService
{
    public function __construct(
        private readonly MovieGenreRepositoryInterface $repository
    ) {}

    /**
     * Return all genres associated with the given movie.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return $this->repository->getByMovieId($movieId);
    }
}
