<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use App\Http\Resources\TvSeasonResource;
use App\Http\Resources\TvShowResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvEpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tv_show_id' => $this->tv_show_id,
            'tv_season_id' => $this->tv_season_id,
            'tmdb_id' => $this->tmdb_id,
            'season_number' => $this->season_number,
            'episode_number' => $this->episode_number,
            'episode_type' => $this->episode_type,
            'production_code' => $this->production_code,
            'name' => $this->name,
            'overview' => $this->overview,
            'air_date' => $this->air_date?->format('Y-m-d'),
            'runtime' => $this->runtime,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'still_path' => ImageHelper::url($this->still_path, 'w780'),
            'tv_season' => $this->whenLoaded('tvSeason', fn () => new TvSeasonResource($this->tvSeason)),
            'tv_show' => $this->whenLoaded('tvShow', fn () => new TvShowResource($this->tvShow)),
        ];
    }
}
