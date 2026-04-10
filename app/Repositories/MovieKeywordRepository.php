<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Keyword;
use App\Repositories\Contracts\MovieKeywordRepositoryInterface;
use Illuminate\Support\Collection;

class MovieKeywordRepository extends BaseRepository implements MovieKeywordRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Keyword);
    }

    /**
     * Get all keywords associated with a given movie via the movie_keywords pivot table.
     */
    public function getByMovieId(int $movieId): Collection
    {
        return Keyword::join('movie_keywords', 'movie_keywords.keyword_id', '=', 'keywords.id')
            ->where('movie_keywords.movie_id', $movieId)
            ->select('keywords.*')
            ->get();
    }
}
