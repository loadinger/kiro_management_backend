<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovieImage extends Model
{
    protected $table = 'movie_images';

    /** @var array<int, string> */
    protected $fillable = [];

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'vote_average' => 'float',
        'vote_count' => 'integer',
    ];
}
