<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TvEpisodeImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tv_episode_id' => $this->tv_episode_id,
            'image_type' => $this->image_type,
            'file_path' => ImageHelper::url($this->file_path, 'w300'),
            'width' => $this->width,
            'height' => $this->height,
            'vote_average' => $this->vote_average,
            'vote_count' => $this->vote_count,
        ];
    }
}
