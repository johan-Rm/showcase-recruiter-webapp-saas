<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use DateTimeImmutable;

/**
 * Représentation typée d'un profil Jarvi ingéré — sous-ensemble en liste blanche du profil pro
 * (Jarvi maître). On ne porte QUE les champs de domaine utiles à showcase-recruiter-webapp-saas, pas les métadonnées
 * d'interface Jarvi (étapes de pipeline, tags internes…). Cf. jarvi-integration.md (tâche 020).
 */
final readonly class JarviProfile
{
    /**
     * @param list<array<string, mixed>> $workExperiences
     * @param list<array<string, mixed>> $educations
     * @param list<array<string, mixed>> $languages
     * @param list<string>               $skills
     * @param list<array<string, mixed>> $resumes
     * @param list<array<string, mixed>> $contacts
     * @param array<string, mixed>       $rawPayload
     */
    public function __construct(
        public string $jarviProfileId,
        public string $firstName,
        public string $lastName,
        public ?string $headline,
        public ?string $city,
        public ?string $country,
        public DateTimeImmutable $updatedAt,
        public ?string $summary = null,
        /** URL publique de la photo chez Jarvi — jamais le binaire, cf. CandidateJarvi::$photoUrl. */
        public ?string $photoUrl = null,
        public array $workExperiences = [],
        public array $educations = [],
        public array $languages = [],
        public array $skills = [],
        public array $resumes = [],
        public array $contacts = [],
        public array $rawPayload = [],
        /**
         * Dernière interaction AVEC le candidat, et son canal (task 121). Rempli sur ~6 % du vivier,
         * mais c'est le seul signal de date honnête : `updatedAt` et ses voisins portent la date
         * d'un import de masse. Sert à ne pas re-contacter quelqu'un, pas à mesurer une fraîcheur.
         */
        public ?DateTimeImmutable $lastInteractionAt = null,
        public ?string $lastInteractionType = null,
    ) {
    }
}
