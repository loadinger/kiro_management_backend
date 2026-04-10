<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface MovieCreditRepositoryInterface
{
    /**
     * Paginate credits for a given movie.
     * movie_id is required to prevent full-table scans.
     * Supported filters: credit_type (cast|crew), page, per_page.
     * Eagerly loads the person relation (person_id may be NULL).
     */
    public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator;
}
