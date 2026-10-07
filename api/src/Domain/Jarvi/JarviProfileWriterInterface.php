<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Écriture ponctuelle d'un profil Jarvi. Utilisée uniquement pour les cas explicitement autorisés
 * (contact déclarant entreprise ou création initiale d'un profil candidat absent), séparée du client
 * de lecture/ingestion.
 */
interface JarviProfileWriterInterface
{
    /**
     * Upsert d'un profil (create or update, idempotent sur `id`/`externalId`). Retourne le
     * `profileId` Jarvi. Lève une exception en cas d'échec (réseau, 4xx/5xx).
     */
    public function upsert(JarviUpsertProfile $profile): string;
}
