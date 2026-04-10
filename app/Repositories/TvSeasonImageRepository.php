<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvSeasonImage;
use App\Repositories\Contracts\TvSeasonImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvSeasonImageRepository extends BaseRepository implements TvSeasonImageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvSeasonImage);
    }

    /**
     * Paginate images for a given tv season.
     * tv_season_id is required to prevent full-table scans.
     */
    public function paginateByTvSeasonId(int $tvSeasonId, array $filters): LengthAwarePaginator
    {
        return TvSeasonImage::where('tv_season_id', $tvSeasonId)
            ->paginate(
                perPage: (int) ($filters['per_page'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }
}
