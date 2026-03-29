<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'iso_3166_1' => $this->iso_3166_1,
            'english_name' => $this->english_name,
            'native_name' => $this->native_name,
        ];
    }
}
