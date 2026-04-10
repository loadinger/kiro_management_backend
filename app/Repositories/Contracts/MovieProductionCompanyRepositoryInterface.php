<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface MovieProductionCompanyRepositoryInterface
{
    /**
     * Get all production companies associated with a given movie.
     * Queries: SELECT production_companies.* FROM production_companies
     *          JOIN movie_production_companies ON movie_production_companies.production_company_id = production_companies.id
     *          WHERE movie_production_companies.movie_id = ?
     */
    public function getByMovieId(int $movieId): Collection;
}
