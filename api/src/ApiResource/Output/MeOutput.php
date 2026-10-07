<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\CandidateOnboardingInput;
use App\State\Processor\CandidateOnboardingProcessor;
use App\State\Provider\MeProvider;

/**
 * Utilisateur courant : identité (Person) + rôles + présence des contextes candidat/recruteur
 * (cumul de casquettes possible) + `candidateId` quand une projection existe. Cf. api-routes.md.
 */
#[ApiResource(
    shortName: 'Me',
    operations: [
        new Get(
            uriTemplate: '/me',
            provider: MeProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: true,
        ),
        // Inscription candidat (public) : crée compte + projection, renvoie le contexte « Me ».
        new Post(
            uriTemplate: '/candidates/onboarding',
            input: CandidateOnboardingInput::class,
            processor: CandidateOnboardingProcessor::class,
            read: false,
        ),
    ],
)]
final class MeOutput
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id,
        public string $email,
        public string $givenName,
        public string $familyName,
        public array $roles,
        public bool $candidate,
        public bool $recruiter,
        public ?string $candidateId = null,
        // État d'enrichissement Jarvi de la projection candidat (PENDING/SYNCED/FAILED), pour piloter
        // l'UX d'attente à l'inscription (tâche 090). Null quand l'utilisateur n'est pas candidat.
        public ?string $candidateSyncStatus = null,
        /**
         * Organisation de la casquette recruteur ACTIVE — `null` sinon.
         *
         * Tout ce qui touche aux crédits et au dévoilement est adressé par organisation ; sans elle
         * ici, le client ne peut construire aucune de ces URL. Un recruteur DÉSACTIVÉ n'en annonce
         * pas : il n'a plus les droits, et l'annoncer ferait afficher un solde qu'il ne peut pas
         * dépenser.
         */
        public ?string $organizationId = null,
    ) {
    }
}
