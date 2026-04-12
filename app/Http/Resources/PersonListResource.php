<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'gender' => $this->gender,
            'adult' => $this->adult,
            'known_for_department' => $this->known_for_department,
            'popularity' => $this->popularity,
            'profile_path' => ImageHelper::url($this->profile_path, 'w185'),
            'created_at' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->updated_at?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
