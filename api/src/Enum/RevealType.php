<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce qu'on dévoile d'un profil. Quatre accès distincts, facturés séparément — un crédit chacun — et
 * INDÉPENDANTS : obtenir le CV n'oblige pas à payer le téléphone.
 *
 * Le découpage suit ce qu'on vient chercher, pas la structure de la donnée : joindre par écrit,
 * joindre de vive voix, lire un parcours, ou lire ce que showcase-recruiter-webapp-saas a constaté en entretien.
 *
 * L'IDENTITÉ N'EST PLUS ICI (task `API-018`). Le nom, la photo, l'employeur et le profil LinkedIn se
 * lisent sans crédit : on paie pour ATTEINDRE quelqu'un, plus pour savoir qui il est. `CONTACT`, qui
 * vendait les deux en bloc, a disparu au profit d'`EMAIL` et de `PHONE`.
 */
enum RevealType: string
{
    /** L'adresse e-mail du candidat. */
    case EMAIL = 'EMAIL';

    /** Le téléphone — et l'identifiant WhatsApp, qui contient le numéro en clair. */
    case PHONE = 'PHONE';

    /** Le CV tel que le candidat l'a déposé. */
    case RESUME = 'RESUME';

    /** Le compte rendu de l'entretien showcase-recruiter-webapp-saas — la promesse « constaté, pas supposé ». */
    case INTERVIEW_REPORT = 'INTERVIEW_REPORT';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
