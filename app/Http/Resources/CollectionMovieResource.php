<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionMovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resolved = $this->movie_id !== null;

        return [
            'movie_tmdb_id' => $this->movie_tmdb_id,
            'movie_id' => $this->movie_id,
            'resolved' => $resolved,
            'title' => $this->title ?? null,
            'original_title' => $this->original_title ?? null,
            'poster_path' => ImageHelper::url($this->movie_poster_path ?? null, 'w342'),
            'release_date' => $this->release_date ?? null,
            'vote_average' => $this->vote_average ?? null,
        ];
    }
}
