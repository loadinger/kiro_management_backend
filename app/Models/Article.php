<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'title',
        'slug',
        'cover_path',
        'content',
        'status',
        'sort_order',
        'published_at',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => ArticleStatus::class,
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'created_by' => 'integer',
    ];

    /**
     * The user who created this article.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The items (entity references) belonging to this article.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ArticleItem::class);
    }
}
