<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleEntityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleItem extends Model
{
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'article_id',
        'entity_type',
        'entity_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'entity_type' => ArticleEntityType::class,
        'entity_id' => 'integer',
        'article_id' => 'integer',
    ];

    /**
     * The article this item belongs to.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
