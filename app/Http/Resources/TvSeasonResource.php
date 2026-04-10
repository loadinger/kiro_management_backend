<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use App\Http\Resources\TvShowResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvSeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tv_show_id' => $this->tv_show_id,
            'tmdb_id' => $this->tmdb_id,
            'season_number' => $this->season_number,
            'name' => $this->name,
            'overview' => $this->overview,
            'air_date' => $this->air_date?->format('Y-m-d'),
            'episode_count' => $this->episode_count,
            'vote_average' => $this->vote_average,
            'poster_path' => ImageHelper::url($this->poster_path, 'w500'),
            'tv_show' => $this->whenLoaded('tvShow', fn () => new TvShowResource($this->tvShow)),
        ];
    }
}
