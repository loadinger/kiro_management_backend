<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';

    /** @var array<int, string> */
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'gender' => 'integer',
        'adult' => 'boolean',
        'birthday' => 'date',
        'deathday' => 'date',
        'popularity' => 'double',
        'also_known_as' => 'array',
    ];
}
