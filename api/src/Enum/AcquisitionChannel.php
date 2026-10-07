<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Par quel canal l'entreprise a connu showcase-recruiter-webapp-saas. Donnée d'acquisition, figée à l'inscription :
 * signal de légitimité pour la revue admin, mesure des canaux ensuite. Non poussée vers Jarvi.
 */
enum AcquisitionChannel: string
{
    case RECOMMANDATION = 'RECOMMANDATION';
    case LINKEDIN = 'LINKEDIN';
    case RECHERCHE_WEB = 'RECHERCHE_WEB';
    case SALON = 'SALON';
    case CONTACT_DIRECT = 'CONTACT_DIRECT';
    case AUTRE = 'AUTRE';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $channel): string => $channel->value, self::cases());
    }
}
