<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'original_language' => $this->original_language,
            'overview' => $this->overview,
            'tagline' => $this->tagline,
            'status' => $this->status,
            'type' => $this->type,
            'first_air_date' => $this->first_air_date?->format('Y-m-d'),
            'last_air_date' => $this->last_air_date?->format('Y-m-d'),
            'number_of_seasons' => $this->number_of_seasons,
            'number_of_episodes' => $this->number_of_episodes,
            'episode_run_time' => $this->episode_run_time,
            'popularity' => $this->popularity,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'adult' => $this->adult,
            'in_production' => $this->in_production,
            'poster_path' => ImageHelper::url($this->poster_path, 'w500'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'original'),
            'homepage' => $this->homepage,
            'origin_country_codes' => $this->origin_country_codes,
            'spoken_language_codes' => $this->spoken_language_codes,
            'language_codes' => $this->language_codes,
            'production_country_codes' => $this->production_country_codes,
            'last_episode_to_air' => $this->last_episode_to_air,
            'next_episode_to_air' => $this->next_episode_to_air,
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->updated_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
