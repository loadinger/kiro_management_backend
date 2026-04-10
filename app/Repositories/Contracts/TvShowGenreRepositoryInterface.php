<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TvShowGenreRepositoryInterface
{
    /**
     * Get all genres associated with a given tv show.
     * Queries: SELECT genres.* FROM genres JOIN tv_show_genres ON tv_show_genres.genre_id = genres.id
     *          WHERE tv_show_genres.tv_show_id = ?
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
