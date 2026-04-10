<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionMovie extends Model
{
    protected $table = 'collection_movies';

    protected $fillable = [];

    public $timestamps = false;
}
