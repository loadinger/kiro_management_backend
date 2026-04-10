<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Keyword;
use App\Repositories\Contracts\TvShowKeywordRepositoryInterface;
use Illuminate\Support\Collection;

class TvShowKeywordRepository extends BaseRepository implements TvShowKeywordRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Keyword);
    }

    /**
     * Get all keywords associated with a given tv show via the tv_show_keywords pivot table.
     */
    public function getByTvShowId(int $tvShowId): Collection
    {
        return Keyword::join('tv_show_keywords', 'tv_show_keywords.keyword_id', '=', 'keywords.id')
            ->where('tv_show_keywords.tv_show_id', $tvShowId)
            ->select('keywords.*')
            ->get();
    }
}
