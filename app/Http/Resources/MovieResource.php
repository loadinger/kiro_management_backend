<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'imdb_id' => $this->imdb_id,
            'title' => $this->title,
            'original_title' => $this->original_title,
            'original_language' => $this->original_language,
            'overview' => $this->overview,
            'tagline' => $this->tagline,
            'status' => $this->status,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'runtime' => $this->runtime,
            'budget' => $this->budget,
            'revenue' => $this->revenue,
            'popularity' => $this->popularity,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
            'adult' => $this->adult,
            'video' => $this->video,
            'poster_path' => ImageHelper::url($this->poster_path, 'w500'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'original'),
            'homepage' => $this->homepage,
            'spoken_language_codes' => $this->spoken_language_codes,
            'production_country_codes' => $this->production_country_codes,
            'collection' => $this->whenLoaded('collection', fn () => $this->collection
                ? new CollectionListResource($this->collection)
                : null
            ),
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->updated_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
