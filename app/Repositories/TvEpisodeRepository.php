<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvEpisode;
use App\Repositories\Contracts\TvEpisodeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeRepository extends BaseRepository implements TvEpisodeRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['episode_number', 'air_date', 'vote_average', 'id'];

    public function __construct()
    {
        parent::__construct(new TvEpisode);
    }

    /**
     * Paginate episodes for a given tv season.
     * tv_season_id is required to prevent full-table scans (2000万+ rows).
     * sort/order: whitelist-validated, default id ASC.
     */
    public function paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator
    {
        $query = TvEpisode::where('tv_season_id', $tvSeasonId);

        $sort = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS, true)
            ? $filters['sort']
            : 'id';

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $order);

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }

    /**
     * Find a tv episode by its local id, with season and show preloaded. Returns null when not found.
     */
    public function findById(int $id): ?TvEpisode
    {
        return TvEpisode::with(['tvSeason', 'tvShow'])->find($id);
    }
}
