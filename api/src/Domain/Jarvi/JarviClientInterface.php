<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use DateTimeImmutable;

/**
 * Lecture du périmètre Jarvi de showcase-recruiter-webapp-saas (tâche 020). Abstraction volontaire : l'ingestion et la
 * réconciliation dépendent de cette interface (DIP) → testables avec un double, sans HTTP réel.
 */
interface JarviClientInterface
{
    /**
     * Profils du périmètre. `null` = pull complet ; `$maxProfiles` borne un run contrôlé.
     *
     * Le périmètre entier ne tient pas en mémoire : le contrat est un flux, page par page, et
     * l'appelant doit consommer sans tout retenir.
     *
     * @return iterable<JarviProfile>
     */
    public function fetchProfiles(?DateTimeImmutable $since = null, ?int $maxProfiles = null): iterable;

    /**
     * Identifiants Jarvi de TOUS les profils actuellement dans le périmètre (réconciliation).
     *
     * @return list<string>
     */
    public function listPerimeterProfileIds(?int $maxProfiles = null): array;

    /**
     * Rafraîchit un profil unique (refresh on-demand au dévoilement).
     */
    public function fetchProfile(string $jarviProfileId): ?JarviProfile;
}
