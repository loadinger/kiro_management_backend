<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface PersonTvShowRepositoryInterface
{
    /**
     * Paginate tv_shows for a given person via tv_episode_credits.
     * Association path: tv_episode_credits → tv_episodes → tv_shows.
     * Uses JOIN + DISTINCT to deduplicate tv_shows.
     * NULL person_id records are naturally excluded by WHERE clause.
     * No N+1 — all data fetched via JOIN.
     */
    public function paginateByPersonId(int $personId, array $filters): LengthAwarePaginator;
}
