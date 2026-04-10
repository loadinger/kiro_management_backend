<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvShow extends Model
{
    protected $table = 'tv_shows';

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'first_air_date' => 'date',
        'last_air_date' => 'date',
        'adult' => 'boolean',
        'in_production' => 'boolean',
        'episode_run_time' => 'array',
        'last_episode_to_air' => 'array',
        'next_episode_to_air' => 'array',
        'origin_country_codes' => 'array',
        'spoken_language_codes' => 'array',
        'language_codes' => 'array',
        'production_country_codes' => 'array',
        'popularity' => 'float',
        'vote_average' => 'float',
        'number_of_seasons' => 'integer',
        'number_of_episodes' => 'integer',
        'vote_count' => 'integer',
    ];
}
