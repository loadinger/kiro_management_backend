<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CreditType;
use App\Models\TvEpisodeCredit;
use App\Repositories\Contracts\TvEpisodeCreditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvEpisodeCreditRepository extends BaseRepository implements TvEpisodeCreditRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TvEpisodeCredit);
    }

    /**
     * Paginate credits for a given episode, eagerly loading the person relation.
     * tv_episode_id is required to prevent full-table scans (extremely large table).
     * person_id may be NULL due to async reconciliation — person will be null in that case.
     */
    public function paginateByTvEpisodeId(int $tvEpisodeId, array $filters): LengthAwarePaginator
    {
        $query = TvEpisodeCredit::where('tv_episode_id', $tvEpisodeId)
            ->with('person');

        if (isset($filters['credit_type'])) {
            $creditType = CreditType::tryFrom($filters['credit_type']);
            if ($creditType !== null) {
                $query->where('credit_type', $creditType);
            }
        }

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
