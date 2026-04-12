<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MovieCredit;
use App\Repositories\Contracts\PersonMovieRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonMovieRepository extends BaseRepository implements PersonMovieRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new MovieCredit);
    }

    /**
     * Paginate movie_credits for a given person.
     * WHERE movie_credits.person_id = $personId naturally excludes NULL person_id records.
     * Eager-loads 'movie' relation to avoid N+1.
     * Default sort: id DESC.
     */
    public function paginateByPersonId(int $personId, array $filters): LengthAwarePaginator
    {
        return MovieCredit::query()
            ->where('person_id', $personId)
            ->with('movie')
            ->orderBy('id', 'desc')
            ->paginate(
                perPage: (int) ($filters['per_page'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }
}
