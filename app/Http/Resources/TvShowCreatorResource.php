<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvShowCreatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tv_show_id' => $this->tv_show_id,
            'person_tmdb_id' => $this->person_tmdb_id,
            'person_id' => $this->person_id,
            'person' => $this->person_id !== null && $this->person !== null
                ? [
                    'id' => $this->person->id,
                    'tmdb_id' => $this->person->tmdb_id,
                    'name' => $this->person->name,
                    'profile_path' => ImageHelper::url($this->person->profile_path, 'w185'),
                ]
                : null,
        ];
    }
}
