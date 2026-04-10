<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvEpisodeImage;
use App\Repositories\Contracts\TvEpisodeImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeImageRepository extends BaseRepository implements TvEpisodeImageRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvEpisodeImage);
    }

    /**
     * Paginate images for a given tv episode.
     * tv_episode_id is required to prevent full-table scans.
     */
    public function paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator
    {
        return TvEpisodeImage::where('tv_episode_id', $tvEpisodeId)
            ->paginate(
                perPage: (int) ($filters['per_page'] ?? 20),
                page: (int) ($filters['page'] ?? 1),
            );
    }
}
