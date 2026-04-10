<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'poster_path' => ImageHelper::url($this->poster_path, 'w342'),
            'backdrop_path' => ImageHelper::url($this->backdrop_path, 'w780'),
        ];
    }
}
