<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListType;
use Illuminate\Database\Eloquent\Model;

class MediaListSnapshot extends Model
{
    protected $table = 'media_list_snapshots';

    /** Read-only table managed by the data collection project. */
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'list_type' => ListType::class,
        'snapshot_date' => 'date',
        'local_id' => 'integer',
        'tmdb_id' => 'integer',
        'rank' => 'integer',
        'popularity' => 'decimal:3',
    ];
}
