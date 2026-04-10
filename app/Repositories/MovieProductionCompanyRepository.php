<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductionCompany;
use App\Repositories\Contracts\MovieProductionCompanyRepositoryInterface;
use Illuminate\Support\Collection;

class MovieProductionCompanyRepository extends BaseRepository implements MovieProductionCompanyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ProductionCompany);
    }

    /**
     * Get all production companies associated with a given movie via the movie_production_companies pivot table.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return ProductionCompany::join(
            'movie_production_companies',
            'movie_production_companies.company_id',
            '=',
            'production_companies.id'
        )
            ->where('movie_production_companies.movie_id', $movieId)
            ->select('production_companies.*')
            ->get();
    }
}
