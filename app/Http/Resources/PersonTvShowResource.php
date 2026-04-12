<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonTvShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'first_air_date' => $this->first_air_date?->format('Y-m-d'),
            'poster_path' => ImageHelper::url($this->poster_path, 'w342'),
            'status' => $this->status,
            'number_of_seasons' => $this->number_of_seasons,
            'number_of_episodes' => $this->number_of_episodes,
        ];
    }
}
