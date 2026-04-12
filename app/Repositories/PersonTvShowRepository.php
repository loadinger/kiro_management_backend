<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvShow;
use App\Repositories\Contracts\PersonTvShowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonTvShowRepository extends BaseRepository implements PersonTvShowRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvShow);
    }

    /**
     * Paginate tv_shows for a given person via tv_episode_credits.
     * JOINs tv_episodes and tv_episode_credits, then uses DISTINCT to deduplicate tv_shows.
     * WHERE clause on person_id naturally excludes NULL person_id records.
     * Default sort: tv_shows.id DESC.
     */
    public function paginateByPersonId(int $personId, array $filters): LengthAwarePaginator
    {
        return TvShow::query()
            ->join('tv_episodes', 'tv_episodes.tv_show_id', '=', 'tv_shows.id')
            ->join('tv_episode_credits', 'tv_episode_credits.tv_episode_id', '=', 'tv_episodes.id')
            ->where('tv_episode_credits.person_id', $personId)
            ->select('tv_shows.*')
            ->distinct()
            ->orderBy('tv_shows.id', 'desc')
            ->paginate(
                perPage: (int) ($filters['per_page'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }
}
