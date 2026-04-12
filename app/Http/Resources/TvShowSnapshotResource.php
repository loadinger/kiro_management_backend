<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use App\Models\MediaListSnapshot;
use App\Models\TvShow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvShowSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MediaListSnapshot $snapshot */
        $snapshot = $this->resource['snapshot'];

        /** @var TvShow|null $tvShow */
        $tvShow = $this->resource['entity'] ?? null;

        return [
            // Snapshot fields
            'rank' => $snapshot->rank,
            'popularity' => $snapshot->popularity,
            'snapshot_date' => $snapshot->snapshot_date?->format('Y-m-d'),
            'tmdb_id' => $snapshot->tmdb_id,
            'local_id' => $snapshot->local_id,

            // TV show entity fields (null when entity not found)
            'id' => $tvShow?->id,
            'name' => $tvShow?->name,
            'original_name' => $tvShow?->original_name,
            'first_air_date' => $tvShow?->first_air_date?->format('Y-m-d'),
            'poster_path' => $tvShow ? ImageHelper::url($tvShow->poster_path, 'w342') : null,
            'vote_average' => $tvShow?->vote_average,
            'status' => $tvShow?->status,
        ];
    }
}
