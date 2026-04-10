<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvShowCreator;
use App\Repositories\Contracts\TvShowCreatorRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowCreatorRepository extends BaseRepository implements TvShowCreatorRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvShowCreator);
    }

    /**
     * Get all creators for a given tv show, eagerly loading the person relation.
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     */
    public function getByTvShowId(int $tvShowId): Collection
    {
        return TvShowCreator::with('person')
            ->where('tv_show_id', $tvShowId)
            ->get();
    }
}
