<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface PersonMovieRepositoryInterface
{
    /**
     * Paginate movie_credits for a given person.
     * Only returns records where person_id = $personId (NULL records are naturally excluded by WHERE clause).
     * Eager-loads 'movie' relation to avoid N+1.
     * Default sort: id DESC.
     */
    public function paginateByPersonId(int $personId, array $filters): LengthAwarePaginator;
}
