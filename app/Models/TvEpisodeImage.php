<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvEpisodeImage extends Model
{
    protected $table = 'tv_episode_images';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];
}
