<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'article_id' => $this->article_id,
            'article' => $this->whenLoaded('article', fn () => [
                'title' => $this->article->title,
                'slug' => $this->article->slug,
                'status' => $this->article->status->value,
                'cover_url' => ImageHelper::url($this->article->cover_path, 'w780'),
            ]),
        ];
    }
}
