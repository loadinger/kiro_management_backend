<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use App\Models\MediaListSnapshot;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MediaListSnapshot $snapshot */
        $snapshot = $this->resource['snapshot'];

        /** @var Movie|null $movie */
        $movie = $this->resource['entity'] ?? null;

        return [
            // Snapshot fields
            'rank' => $snapshot->rank,
            'popularity' => $snapshot->popularity,
            'snapshot_date' => $snapshot->snapshot_date?->format('Y-m-d'),
            'tmdb_id' => $snapshot->tmdb_id,
            'local_id' => $snapshot->local_id,

            // Movie entity fields (null when entity not found)
            'id' => $movie?->id,
            'title' => $movie?->title,
            'original_title' => $movie?->original_title,
            'release_date' => $movie?->release_date?->format('Y-m-d'),
            'poster_path' => $movie ? ImageHelper::url($movie->poster_path, 'w342') : null,
            'vote_average' => $movie?->vote_average,
            'status' => $movie?->status,
        ];
    }
}
