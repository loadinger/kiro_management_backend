<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\MovieCreditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieCreditService
{
    public function __construct(
        private readonly MovieCreditRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of credits for the given movie.
     * Supported filters: credit_type (cast|crew), page, per_page.
     */
    public function getList(int $movieId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByMovieId($movieId, $filters);
    }
}
