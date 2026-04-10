<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'description' => $this->description,
            'headquarters' => $this->headquarters,
            'homepage' => $this->homepage,
            'logo_path' => ImageHelper::url($this->logo_path, 'w342'),
            'origin_country' => $this->origin_country,
            'parent_company_tmdb_id' => $this->parent_company_tmdb_id,
        ];
    }
}
