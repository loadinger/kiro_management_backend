<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $table = 'collections';

    protected $fillable = [];

    public $timestamps = false;

    public function movies(): HasMany
    {
        return $this->hasMany(CollectionMovie::class, 'collection_id');
    }
}
