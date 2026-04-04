<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $table = 'keywords';

    protected $fillable = [
        'name_zh',
        'translated_at',
    ];

    protected $casts = [
        'translated_at' => 'datetime',
    ];
}
