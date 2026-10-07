<?php

declare(strict_types=1);

namespace App\Enum;

enum CandidateOptionType: string
{
    case BADGE_PREMIUM = 'BADGE_PREMIUM';
    case HOMEPAGE_FEATURE = 'HOMEPAGE_FEATURE';
    case SEARCH_BOOST = 'SEARCH_BOOST';
    case RECRUITER_SELECTION = 'RECRUITER_SELECTION';
}
