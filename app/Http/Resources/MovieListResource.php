<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'original_title' => $this->original_title,
            'original_language' => $this->original_language,
            'status' => $this->status,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'runtime' => $this->runtime,
            'popularity' => $this->popularity,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'adult' => $this->adult,
            'poster_path' => ImageHelper::url($this->poster_path, 'w342'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'w780'),
        ];
    }
}
