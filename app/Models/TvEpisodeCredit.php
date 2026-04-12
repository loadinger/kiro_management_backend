<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreditType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvEpisodeCredit extends Model
{
    protected $table = 'tv_episode_credits';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'credit_type' => CreditType::class,
    ];

    /**
     * Get the person associated with this credit.
     * person_id may be NULL due to async reconciliation.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function tvEpisode(): BelongsTo
    {
        return $this->belongsTo(TvEpisode::class);
    }
}
