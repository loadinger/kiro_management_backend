<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Models\TvEpisode;
use App\Repositories\Contracts\TvEpisodeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeService
{
    public function __construct(
        private readonly TvEpisodeRepositoryInterface $repository
    ) {}

    /**
     * Return a paginated list of episodes for the given tv season.
     * Large table constraint (20M+ rows): tv_season_id is required.
     * Supported filters: sort, order, page, per_page.
     */
    public function getList(int $tvSeasonId, array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByTvSeasonId($tvSeasonId, $filters);
    }

    /**
     * Find an episode by its local ID.
     *
     * @throws AppException when the record does not exist
     */
    public function findById(int $id): TvEpisode
    {
        $episode = $this->repository->findById($id);

        if ($episode === null) {
            throw new AppException('集不存在', 404);
        }

        return $episode;
    }
}
