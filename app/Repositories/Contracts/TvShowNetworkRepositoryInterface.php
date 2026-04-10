<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TvShowNetworkRepositoryInterface
{
    /**
     * Get all tv networks associated with a given tv show.
     * Queries: SELECT tv_networks.* FROM tv_networks JOIN tv_show_networks ON tv_show_networks.network_id = tv_networks.id
     *          WHERE tv_show_networks.tv_show_id = ?
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
