<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'gender' => $this->gender,
            'adult' => $this->adult,
            'biography' => $this->biography,
            'place_of_birth' => $this->place_of_birth,
            'known_for_department' => $this->known_for_department,
            'popularity' => $this->popularity,
            'homepage' => $this->homepage,
            'imdb_id' => $this->imdb_id,
            'also_known_as' => $this->also_known_as,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'deathday' => $this->deathday?->format('Y-m-d'),
            'profile_path' => ImageHelper::url($this->profile_path, 'w342'),
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->updated_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
