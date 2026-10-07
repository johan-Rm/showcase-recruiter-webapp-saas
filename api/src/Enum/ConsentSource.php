<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Canal par lequel le consentement candidat a été recueilli (tâche 033). Trace RGPD : le
 * consentement est explicite, horodaté (`Candidate.consentGivenAt`) et attribué à sa source.
 */
enum ConsentSource: string
{
    case SELF_SERVICE = 'SELF_SERVICE';
    case ONBOARDING = 'ONBOARDING';
    case MAGIC_LINK = 'MAGIC_LINK';

    /**
     * Ouvert POUR ÊTRE MONTRÉ, sans que personne n'ait rien donné — task API-032.
     *
     * Les trois autres affirment un geste du candidat. Un environnement de démonstration a besoin
     * d'un vivier exposable, et emprunter l'une d'elles écrirait que trois mille personnes se sont
     * inscrites : la donnée mentirait, et plus rien ne distinguerait les vrais consentements.
     *
     * C'est aussi ce qui rend le geste réversible — `app:candidates:consent-demo --revoke` referme
     * exactement les profils portant cette source, et un consentement réel y survit.
     *
     * N'apparaît jamais au terme d'un parcours candidat : seule une commande d'amorçage la pose.
     */
    case DEMONSTRATION = 'DEMONSTRATION';
}
