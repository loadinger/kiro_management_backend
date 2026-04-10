<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Movie extends Model
{
    protected $table = 'movies';

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'release_date' => 'date',
        'adult' => 'boolean',
        'video' => 'boolean',
        'spoken_language_codes' => 'array',
        'production_country_codes' => 'array',
        'popularity' => 'float',
        'vote_average' => 'float',
        'budget' => 'integer',
        'revenue' => 'integer',
        'runtime' => 'integer',
        'vote_count' => 'integer',
    ];

    /**
     * A movie belongs to at most one collection, via the collection_movies pivot.
     */
    public function collection(): HasOneThrough
    {
        return $this->hasOneThrough(
            Collection::class,
            CollectionMovie::class,
            'movie_id',       // FK on collection_movies
            'id',             // FK on collections
            'id',             // local key on movies
            'collection_id',  // local key on collection_movies
        );
    }
}
