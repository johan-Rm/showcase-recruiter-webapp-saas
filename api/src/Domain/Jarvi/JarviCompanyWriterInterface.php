<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

interface JarviCompanyWriterInterface
{
    /**
     * Crée ou met à jour une company Jarvi et retourne son `companyId`.
     */
    public function upsert(JarviUpsertCompany $company): string;

    /**
     * Rattache la company Jarvi au projet recruteur showcase-recruiter-webapp-saas cible.
     */
    public function attachToProject(string $projectId, string $companyId): void;
}
