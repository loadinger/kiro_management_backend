<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvEpisode extends Model
{
    protected $table = 'tv_episodes';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'air_date' => 'date',
        'vote_average' => 'float',
        'vote_count' => 'integer',
        'runtime' => 'integer',
        'season_number' => 'integer',
        'episode_number' => 'integer',
    ];

    public function tvSeason(): BelongsTo
    {
        return $this->belongsTo(TvSeason::class, 'tv_season_id');
    }

    public function tvShow(): BelongsTo
    {
        return $this->belongsTo(TvShow::class, 'tv_show_id');
    }
}
