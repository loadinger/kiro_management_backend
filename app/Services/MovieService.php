<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\Movie;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieService
{
    public function __construct(
        private readonly MovieRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of movies with optional filters.
     * Supported filters: q, genre_id, status, release_year, adult, sort, order, page, per_page.
     */
    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a movie by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): Movie
    {
        $movie = $this->repository->findById($id);

        if ($movie === null) {
            throw new AppException('电影不存在', 404);
        }

        return $movie;
    }
}
