<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvSeason extends Model
{
    protected $table = 'tv_seasons';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'air_date' => 'date',
        'vote_average' => 'float',
        'episode_count' => 'integer',
        'season_number' => 'integer',
    ];

    public function tvShow(): BelongsTo
    {
        return $this->belongsTo(TvShow::class, 'tv_show_id');
    }
}
