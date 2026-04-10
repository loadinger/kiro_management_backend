<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvSeasonImage extends Model
{
    protected $table = 'tv_season_images';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];
}
