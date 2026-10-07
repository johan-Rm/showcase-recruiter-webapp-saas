<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Ordre asynchrone : soumettre un candidat fraîchement inscrit à Jarvi via `/applicants` puis
 * ancrer sa projection sur le `profileId` retourné (tâche 089). Non bloquant pour l'inscription,
 * qui a déjà répondu 201.
 *
 * Le CV éventuel voyage en base64 DANS le message (jamais copié dans le modèle maître showcase-recruiter-webapp-saas) :
 * il est consommé puis effacé avec le message une fois transmis à Jarvi.
 */
final readonly class SubmitCandidateToJarvi
{
    public function __construct(
        public string $candidateId,
        public ?string $cvFileName = null,
        public ?string $cvBase64 = null,
    ) {
    }
}
