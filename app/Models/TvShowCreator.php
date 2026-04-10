<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvShowCreator extends Model
{
    protected $table = 'tv_show_creators';

    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [];

    /**
     * Get the person associated with this creator.
     * person_id may be NULL due to async reconciliation.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
