<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditType: string
{
    case Cast = 'cast';
    case Crew = 'crew';
}
