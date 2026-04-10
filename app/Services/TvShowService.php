<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\TvShow;
use App\Repositories\Contracts\TvShowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvShowService
{
    public function __construct(
        private readonly TvShowRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of tv shows with optional filters.
     * Supported filters: q, genre_id, status, first_air_year, in_production, sort, order, page, per_page.
     */
    public function getList(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters);
    }

    /**
     * Find a tv show by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): TvShow
    {
        $tvShow = $this->repository->findById($id);

        if ($tvShow === null) {
            throw new AppException('电视剧不存在', 404);
        }

        return $tvShow;
    }
}
