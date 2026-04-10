<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TvShowKeywordRepositoryInterface
{
    /**
     * Get all keywords associated with a given tv show.
     * Queries: SELECT keywords.* FROM keywords JOIN tv_show_keywords ON tv_show_keywords.keyword_id = keywords.id
     *          WHERE tv_show_keywords.tv_show_id = ?
     */
    public function getByTvShowId(int $tvShowId): Collection;
}
