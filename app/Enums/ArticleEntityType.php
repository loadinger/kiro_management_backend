<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleEntityType: string
{
    case Movie = 'movie';
    case Collection = 'collection';
    case TvShow = 'tv_show';
    case TvSeason = 'tv_season';
    case TvEpisode = 'tv_episode';
    case Person = 'person';
    case ProductionCompany = 'production_company';
    case TvNetwork = 'tv_network';
    case Genre = 'genre';
    case Keyword = 'keyword';
}
