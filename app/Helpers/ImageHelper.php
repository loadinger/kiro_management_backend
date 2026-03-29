<?php

declare(strict_types=1);

namespace App\Helpers;

class ImageHelper
{
    private const BASE_URL = 'https://image.tmdb.org/t/p';

    /**
     * Build a full TMDB image URL from a relative path and size.
     * Returns null when path is null.
     */
    public static function url(?string $path, string $size): ?string
    {
        if ($path === null) {
            return null;
        }

        return self::BASE_URL.'/'.$size.$path;
    }
}
