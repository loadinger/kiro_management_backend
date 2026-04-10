<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvNetwork;
use App\Repositories\Contracts\TvShowNetworkRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowNetworkRepository extends BaseRepository implements TvShowNetworkRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvNetwork);
    }

    /**
     * Get all tv networks associated with a given tv show via the tv_show_networks pivot table.
     */
    public function getByTvShowId(int $tvShowId): Collection
    {
        return TvNetwork::join('tv_show_networks', 'tv_show_networks.network_id', '=', 'tv_networks.id')
            ->where('tv_show_networks.tv_show_id', $tvShowId)
            ->select('tv_networks.*')
            ->get();
    }
}
