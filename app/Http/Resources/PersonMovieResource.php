<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonMovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movie_id,
            'credit_type' => $this->credit_type->value,
            'character' => $this->character,
            'cast_order' => $this->cast_order,
            'department_id' => $this->department_id,
            'job_id' => $this->job_id,
            'tmdb_id' => $this->movie->tmdb_id,
            'title' => $this->movie->title,
            'original_title' => $this->movie->original_title,
            'release_date' => $this->movie->release_date?->format('Y-m-d'),
            'poster_path' => ImageHelper::url($this->movie->poster_path, 'w342'),
        ];
    }
}
