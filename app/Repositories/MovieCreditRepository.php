<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MovieCredit;
use App\Repositories\Contracts\MovieCreditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class MovieCreditRepository extends BaseRepository implements MovieCreditRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new MovieCredit);
    }

    /**
     * Paginate credits for a given movie, eagerly loading the person relation.
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     */
    public function paginateByMovieId(int $movieId, array $filters): LengthAwarePaginator
    {
        $query = MovieCredit::with('person')
            ->where('movie_id', $movieId);

        if (! empty($filters['credit_type'])) {
            $query->where('credit_type', $filters['credit_type']);
        }

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
