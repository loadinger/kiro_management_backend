<?php

declare(strict_types=1);

namespace App\Enums;

enum ListType: string
{
    case MovieTrendingDay = 'movie_trending_day';
    case MovieTrendingWeek = 'movie_trending_week';
    case MovieNowPlaying = 'movie_now_playing';
    case MovieUpcoming = 'movie_upcoming';
    case TvTrendingDay = 'tv_trending_day';
    case TvTrendingWeek = 'tv_trending_week';
    case TvAiringToday = 'tv_airing_today';
    case TvOnTheAir = 'tv_on_the_air';
    case PersonTrendingDay = 'person_trending_day';
    case PersonTrendingWeek = 'person_trending_week';

    public function isMovie(): bool
    {
        return in_array($this, [
            self::MovieTrendingDay,
            self::MovieTrendingWeek,
            self::MovieNowPlaying,
            self::MovieUpcoming,
        ], true);
    }

    public function isTvShow(): bool
    {
        return in_array($this, [
            self::TvTrendingDay,
            self::TvTrendingWeek,
            self::TvAiringToday,
            self::TvOnTheAir,
        ], true);
    }

    public function isPerson(): bool
    {
        return in_array($this, [
            self::PersonTrendingDay,
            self::PersonTrendingWeek,
        ], true);
    }
}
