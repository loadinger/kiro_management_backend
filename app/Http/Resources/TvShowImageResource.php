<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvShowImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // backdrop type uses w780, all other types (poster, logo) use w342
        $size = $this->image_type === 'backdrop' ? 'w780' : 'w342';

        return [
            'id' => $this->id,
            'tv_show_id' => $this->tv_show_id,
            'image_type' => $this->image_type,
            'file_path' => ImageHelper::url($this->file_path, $size),
            'width' => $this->width,
            'height' => $this->height,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
        ];
    }
}
