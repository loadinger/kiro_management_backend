<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\TvSeason;
use App\Repositories\Contracts\TvSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TvSeasonService
{
    public function __construct(
        private readonly TvSeasonRepositoryInterface $repository
    ) {}

    /**
     * Return all seasons for the given tv show, ordered by season_number.
     * tv_show_id is required (large table constraint).
     */
    public function getAll(int $tvShowId, array $filters): Collection
    {
        return $this->repository->getAllByTvShowId($tvShowId, $filters);
    }

    /**
     * Find a season by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): TvSeason
    {
        $season = $this->repository->findById($id);

        if ($season === null) {
            throw new AppException('季不存在', 404);
        }

        return $season;
    }
}
