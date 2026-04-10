<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface MovieGenreRepositoryInterface
{
    /**
     * Get all genres associated with a given movie.
     * Queries: SELECT genres.* FROM genres JOIN movie_genres ON movie_genres.genre_id = genres.id
     *          WHERE movie_genres.movie_id = ?
     */
    public function getByMovieId(int $movieId): Collection;
}
