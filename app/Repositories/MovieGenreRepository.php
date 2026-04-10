<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Genre;
use App\Repositories\Contracts\MovieGenreRepositoryInterface;
use Illuminate\Support\Collection;

class MovieGenreRepository extends BaseRepository implements MovieGenreRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Genre);
    }

    /**
     * Get all genres associated with a given movie via the movie_genres pivot table.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return Genre::join('movie_genres', 'movie_genres.genre_id', '=', 'genres.id')
            ->where('movie_genres.movie_id', $movieId)
            ->select('genres.*')
            ->get();
    }
}
