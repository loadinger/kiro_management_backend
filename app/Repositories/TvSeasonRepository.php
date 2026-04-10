<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvSeason;
use App\Repositories\Contracts\TvSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TvSeasonRepository extends BaseRepository implements TvSeasonRepositoryInterface
{
    /** Allowed sort fields to prevent SQL injection via orderBy. */
    private const ALLOWED_SORTS = ['season_number', 'air_date', 'vote_average', 'id'];

    public function __construct()
    {
        parent::__construct(new TvSeason);
    }

    /**
     * Return all seasons for a given tv show.
     * tv_show_id is required to prevent full-table scans (100万+ rows).
     */
    public function getAllByTvShowId(int $tvShowId, array $filters): Collection
    {
        $sort = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS, true)
            ? $filters['sort']
            : 'season_number';

        $order = ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return TvSeason::where('tv_show_id', $tvShowId)
            ->orderBy($sort, $order)
            ->get();
    }

    /**
     * Find a tv season by its local id, with tv show preloaded. Returns null when not found.
     */
    public function findById(int $id): ?TvSeason
    {
        return TvSeason::with('tvShow')->find($id);
    }
}
