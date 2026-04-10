<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'overview' => $this->overview,
            'poster_path' => ImageHelper::url($this->poster_path, 'w500'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'original'),
            'movies' => CollectionMovieResource::collection($this->whenLoaded('movies')),
        ];
    }
}
