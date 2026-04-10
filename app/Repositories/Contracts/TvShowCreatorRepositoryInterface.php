<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TvShowCreatorRepositoryInterface
{
    /**
     * Get all creators for a given tv show, eagerly loading the person relation.
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
