<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CurrentPositionCertainty;
use App\Enum\EducationLevel;
use App\Enum\SeniorityLevel;
use App\Enum\SyncStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réplica JSON OPAQUE du profil pro Jarvi (1-1 Candidate), en LECTURE / SYNCHRO ONLY — Jarvi
 * maître. showcase-recruiter-webapp-saas n'édite ni ne normalise ces données : chaque bloc (expériences, formations,
 * langues, compétences, CV, contacts) est stocké tel quel en JSON dont la structure interne suit
 * Jarvi (cf. dev-book/cadrage/candidat/profil-jarvi-reference.md). Alimentée par l'ingestion
 * (tâche 020). Liste blanche de champs : les comptes rendus Jarvi sensibles restent dans
 * rawPayload, sans promotion en vérité locale showcase-recruiter-webapp-saas. Cf. data-model.md § CandidateJarvi.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_jarvi')]
#[ORM\Index(name: 'idx_candidate_jarvi_updated_at', columns: ['jarvi_updated_at'])]
class CandidateJarvi extends AbstractEntity
{
    /**
     * @param list<array<string, mixed>> $workExperiences
     * @param list<array<string, mixed>> $educations
     * @param list<array<string, mixed>> $languages
     * @param list<string>               $skills
     * @param list<array<string, mixed>> $resumes
     * @param list<array<string, mixed>> $contacts
     * @param array<string, mixed>|null  $rawPayload
     */
    public function __construct(
        #[ORM\OneToOne(targetEntity: Candidate::class, inversedBy: 'jarvi')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
        private Candidate $candidate,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $headline = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $summary = null,
        // Photo : URL publique du storage Jarvi, jamais le binaire (pas de copie, pas de proxy).
        // L'URL est servie sans authentification → elle vaut identité et ne sort qu'au dévoilement.
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $photoUrl = null,
        #[ORM\Column(type: 'json')]
        public array $workExperiences = [],
        #[ORM\Column(type: 'json')]
        public array $educations = [],
        #[ORM\Column(type: 'json')]
        public array $languages = [],
        #[ORM\Column(type: 'json')]
        public array $skills = [],
        // CV : métadonnées uniquement (fileId, name, mimeType, parsedAt). Binaire NON copié : servi
        // à la demande via l'URL de download du storage Jarvi (.../v1/files/<fileId>). Mode d'auth de
        // l'URL à valider (public par fileId vs jeton) → éventuel proxy côté API.
        #[ORM\Column(type: 'json')]
        public array $resumes = [],
        #[ORM\Column(type: 'json')]
        public array $contacts = [],
        // Seule donnée NORMALISÉE de la réplica : années d'expérience dérivées des périodes des
        // `workExperiences` à l'ingestion (Jarvi ne les fournit pas). Colonne scalaire parce qu'elle
        // est à la fois affichée (colonne « Exp. » de la Table) et filtrable — pas de calcul à la volée.
        #[ORM\Column(type: 'integer')]
        public int $experienceYears = 0,
        // Mêmes colonnes normalisées qu'experienceYears (dérivées à l'ingestion, affichées et
        // filtrables sans recalcul) : durée sur le poste actuel en mois, et séniorité dérivée de
        // l'ancienneté (task 095).
        #[ORM\Column(type: 'integer')]
        public int $currentRoleMonths = 0,
        // Poste ACTUEL (task 117). La définition « position sans date de fin » ne tranche que 53 %
        // des profils : la certitude est portée explicitement, et ABSENT n'est jamais confondu avec
        // AMBIGUOUS — « pas de poste en cours » et « impossible de trancher » sont opposés.
        #[ORM\Column(enumType: CurrentPositionCertainty::class)]
        public CurrentPositionCertainty $currentPositionCertainty = CurrentPositionCertainty::ABSENT,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $currentTitle = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $currentCompanyName = null,
        #[ORM\ManyToOne(targetEntity: VocabularyConcept::class)]
        #[ORM\JoinColumn(name: 'current_job_concept_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
        public ?VocabularyConcept $currentJobConcept = null,
        #[ORM\Column(enumType: SeniorityLevel::class)]
        public SeniorityLevel $seniority = SeniorityLevel::JUNIOR,
        // Niveau d'études dérivé des titres de formation (task 112). NULLABLE à dessein : un
        // parcours dont aucun intitulé n'est reconnaissable n'a pas de niveau, plutôt qu'un faux.
        #[ORM\Column(enumType: EducationLevel::class, nullable: true)]
        public ?EducationLevel $educationLevel = null,
        #[ORM\Column(type: 'json', nullable: true)]
        public ?array $rawPayload = null,
        // Dernière modification du profil CHEZ JARVI (task 116) — à ne pas confondre avec `syncedAt`,
        // qui date NOTRE lecture. C'est le seul signal de fraîcheur exploitable : 98 % du vivier vient
        // du sourcing LinkedIn, donc sans CV ni activité à dater. Nullable : une date absente n'est
        // jamais remplacée par la nôtre, qui ferait passer une donnée ancienne pour fraîche.
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $jarviUpdatedAt = null,
        // Dernier contact AVEC le candidat et son canal (task 121). Rempli sur ~6 % du vivier, mais
        // c'est le seul signal de date honnête : `jarviUpdatedAt`, `addedAt`, `createdAt` et
        // `lastResumeUpdatedAt` portent tous, à ~83 %, la date d'un même import de masse.
        // Sert à NE PAS re-contacter quelqu'un — jamais à mesurer une fraîcheur de profil.
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $lastInteractionAt = null,
        #[ORM\Column(length: 64, nullable: true)]
        public ?string $lastInteractionType = null,
        #[ORM\Column(enumType: SyncStatus::class)]
        public SyncStatus $syncStatus = SyncStatus::PENDING,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $syncedAt = null,
    ) {
        parent::__construct();
    }

    public function getCandidate(): Candidate
    {
        return $this->candidate;
    }
}
