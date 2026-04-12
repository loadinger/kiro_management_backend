<?php

declare(strict_types=1);

namespace App\Helpers;

class ImageHelper
{
    /**
     * Return the relative TMDB image path as-is.
     * Returns null when path is null.
     * The $size parameter is kept for call-site compatibility but is not used.
     */
    public static function url(?string $path, string $size): ?string
    {
        return $path;
    }
}
