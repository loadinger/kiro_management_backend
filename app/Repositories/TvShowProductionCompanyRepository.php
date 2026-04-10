<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductionCompany;
use App\Repositories\Contracts\TvShowProductionCompanyRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowProductionCompanyRepository extends BaseRepository implements TvShowProductionCompanyRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new ProductionCompany);
    }

    /**
     * Get all production companies associated with a given tv show via the tv_show_production_companies pivot table.
     */
    public function getByTvShowId(int $tvShowId): Collection
    {
        return ProductionCompany::join(
            'tv_show_production_companies',
            'tv_show_production_companies.company_id',
            '=',
            'production_companies.id'
        )
            ->where('tv_show_production_companies.tv_show_id', $tvShowId)
            ->select('production_companies.*')
            ->get();
    }
}
