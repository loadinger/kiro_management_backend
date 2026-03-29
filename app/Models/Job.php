<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
