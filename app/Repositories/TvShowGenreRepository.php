<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\Contracts\TvShowGenreRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowGenreRepository extends BaseRepository implements TvShowGenreRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Genre);
    }

    /**
     * Get all genres associated with a given tv show via the tv_show_genres pivot table.
     */
    public function getByTvShowId(int $tvShowId): Collection
    {
        return Genre::join('tv_show_genres', 'tv_show_genres.genre_id', '=', 'genres.id')
            ->where('tv_show_genres.tv_show_id', $tvShowId)
            ->select('genres.*')
            ->get();
    }
}
