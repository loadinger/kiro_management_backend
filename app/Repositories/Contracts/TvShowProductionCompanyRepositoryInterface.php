<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TvShowProductionCompanyRepositoryInterface
{
    /**
     * Get all production companies associated with a given tv show.
     * Queries: SELECT production_companies.* FROM production_companies
     *          JOIN tv_show_production_companies ON tv_show_production_companies.company_id = production_companies.id
     *          WHERE tv_show_production_companies.tv_show_id = ?
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
