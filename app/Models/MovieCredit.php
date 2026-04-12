<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreditType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieCredit extends Model
{
    protected $table = 'movie_credits';

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'credit_type' => CreditType::class,
        'cast_order' => 'integer',
    ];

    /**
     * Get the person associated with this credit.
     * person_id may be NULL due to async reconciliation.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Get the movie associated with this credit.
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
