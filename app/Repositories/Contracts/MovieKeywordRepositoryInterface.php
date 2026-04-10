<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface MovieKeywordRepositoryInterface
{
    /**
     * Get all keywords associated with a given movie.
     * Queries: SELECT keywords.* FROM keywords JOIN movie_keywords ON movie_keywords.keyword_id = keywords.id
     *          WHERE movie_keywords.movie_id = ?
     */
    public function getByMovieId(int $movieId): Collection;
}
