<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvShowListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'original_language' => $this->original_language,
            'status' => $this->status,
            'first_air_date' => $this->first_air_date?->format('Y-m-d'),
            'number_of_seasons' => $this->number_of_seasons,
            'number_of_episodes' => $this->number_of_episodes,
            'in_production' => $this->in_production,
            'popularity' => $this->popularity,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'adult' => $this->adult,
            'poster_path' => ImageHelper::url($this->poster_path, 'w342'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'w780'),
        ];
    }
}
