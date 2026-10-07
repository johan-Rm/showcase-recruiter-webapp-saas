<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Écriture montante d'un applicant Jarvi (`POST /applicants`), seul canal qui déclenche
 * l'extraction du profil (LinkedIn + CV). Séparé de {@see JarviProfileWriterInterface} (`/profiles`).
 */
interface JarviApplicantWriterInterface
{
    /**
     * Soumet l'applicant (upsert idempotent sur `externalId`). Retourne l'ancre Jarvi (`profileId`)
     * et le contexte d'extraction. Lève {@see JarviWriteException} en cas d'échec (réseau, 4xx/5xx).
     */
    public function submit(JarviApplicant $applicant): JarviApplicantResult;
}
