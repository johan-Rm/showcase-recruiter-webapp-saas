<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Availability;
use App\Enum\ConsentSource;
use App\Enum\EducationLevel;
use App\Enum\ProfileVisibility;
use App\Enum\ReturnHorizon;
use App\Enum\SyncStatus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Projection candidat showcase-recruiter-webapp-saas ancrée sur `jarviProfileId` (clé unique). Ne porte QUE les
 * différenciants MAÎTRES showcase-recruiter-webapp-saas (présence Maroc, horizon/projet de retour, attache au Maroc,
 * prétentions, disponibilité, visibilité), conservés localement. Le profil pro
 * (CV, expériences, formations, langues, compétences) vit dans `CandidateJarvi` — réplica JSON
 * opaque, lecture/synchro only, Jarvi maître (cf. data-model.md, abandon des tables réplica
 * normalisées le 2026-06-28). Le compte (`User`) est optionnel : un profil du périmètre peut
 * exister sans inscription. `active = false` = profil sorti du périmètre (désactivé par la
 * réconciliation, tâche 020), jamais supprimé.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate')]
class Candidate extends AbstractEntity
{
    #[ORM\Embedded(class: MonetaryAmount::class)]
    public MonetaryAmount $salaryExpected;

    #[ORM\Embedded(class: MonetaryAmount::class)]
    public MonetaryAmount $salaryMin;

    /** Réplica pro Jarvi (JSON opaque), absente tant que le profil n'a pas été ingéré (tâche 020). */
    #[ORM\OneToOne(targetEntity: CandidateJarvi::class, mappedBy: 'candidate', cascade: ['persist', 'remove'])]
    private ?CandidateJarvi $jarvi = null;

    /** @var Collection<int, PresencePeriod> */
    #[ORM\OneToMany(targetEntity: PresencePeriod::class, mappedBy: 'candidate', cascade: ['persist', 'remove'])]
    private Collection $presencePeriods;

    /**
     * Expérience par secteur, DÉRIVÉE à l'ingestion (task 095) : entièrement remplacée à chaque
     * réingestion, d'où `orphanRemoval` — aucune ligne ne survit à un recalcul.
     *
     * @var Collection<int, CandidateSectorExperience>
     */
    #[ORM\OneToMany(targetEntity: CandidateSectorExperience::class, mappedBy: 'candidate', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sectorExperiences;

    /**
     * Métiers exercés et durée associée, DÉRIVÉS à l'ingestion (task 095) : entièrement remplacés à
     * chaque réingestion, d'où `orphanRemoval`.
     *
     * @var Collection<int, CandidateJobExperience>
     */
    #[ORM\OneToMany(targetEntity: CandidateJobExperience::class, mappedBy: 'candidate', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $jobExperiences;

    /**
     * Intitulés de poste du parcours, DÉRIVÉS à l'ingestion (task API-037) — la LETTRE, là où les
     * métiers exercés portent le SENS. Ils rendent cherchable ce que le dictionnaire ignore.
     *
     * @var Collection<int, CandidateJobTitle>
     */
    #[ORM\OneToMany(targetEntity: CandidateJobTitle::class, mappedBy: 'candidate', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $jobTitles;

    /**
     * Langues parlées, DÉRIVÉES à l'ingestion (task 114) : entièrement remplacées à chaque
     * réingestion, d'où `orphanRemoval`.
     *
     * @var Collection<int, CandidateLanguage>
     */
    #[ORM\OneToMany(targetEntity: CandidateLanguage::class, mappedBy: 'candidate', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $languages;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Person::class, cascade: ['persist'])]
        #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', nullable: false, unique: true)]
        private Person $person,
        #[ORM\Column(type: 'uuid', unique: true)]
        public Uuid $jarviProfileId,
        // Secteur et fonction visés : valeurs LIBRES, telles que le profil pro les porte (Jarvi en est
        // maître). Les listes du cockpit se déduisent des valeurs réellement présentes dans le vivier
        // (facettes), pas d'un vocabulaire curé à maintenir en parallèle.
        #[ORM\Column(length: 120, nullable: true)]
        public ?string $desiredSector = null,
        #[ORM\Column(length: 120, nullable: true)]
        public ?string $desiredFunction = null,
        // ★ Projet de retour au Maroc (maître showcase-recruiter-webapp-saas, local).
        #[ORM\Column(enumType: ReturnHorizon::class, nullable: true)]
        public ?ReturnHorizon $returnHorizon = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $returnProject = null,
        // ★ Attache au Maroc (maître showcase-recruiter-webapp-saas, local).
        #[ORM\Column(type: 'boolean')]
        public bool $hasPiedATerre = false,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $piedATerreCity = null,
        #[ORM\Column(type: 'boolean')]
        public bool $geoMobility = false,
        // Disponibilité (préavis) — distincte de l'horizon de retour.
        #[ORM\Column(enumType: Availability::class, nullable: true)]
        public ?Availability $availability = null,
        // Niveau d'études le plus élevé, DÉRIVÉ des formations à l'ingestion (task 112) et projeté
        // ici pour être filtrable — le calcul reste porté par la réplica, Jarvi maître. Nullable :
        // aucun niveau par défaut (un titre illisible ne produit rien, cf. EducationLevelCalculator).
        #[ORM\Column(enumType: EducationLevel::class, nullable: true)]
        public ?EducationLevel $educationLevel = null,
        #[ORM\Column(enumType: ProfileVisibility::class)]
        public ProfileVisibility $visibility = ProfileVisibility::VISIBLE,
        #[ORM\Column(enumType: SyncStatus::class)]
        public SyncStatus $syncStatus = SyncStatus::PENDING,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $syncedAt = null,
        // Appartenance au périmètre Jarvi de showcase-recruiter-webapp-saas : false = sorti du périmètre (désactivé
        // par la réconciliation quotidienne, tâche 020), jamais supprimé.
        #[ORM\Column(type: 'boolean')]
        public bool $active = true,
        // Horodatage du dernier choix de visibilité = trace de consentement (RGPD, tâche 018).
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $visibilityUpdatedAt = null,
        // Profil Star = entretien showcase-recruiter-webapp-saas réalisé ; le compte rendu n'est exposé qu'après dévoilement.
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $starInterviewedAt = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $starInterviewReport = null,
        // Consentement showcase-recruiter-webapp-saas explicite et horodaté (RGPD, tâche 033) : condition d'exposition du
        // profil aux recruteurs. Distinct de `visibilityUpdatedAt` (choix d'exposition). Un profil
        // importé de Jarvi naît SANS consentement (`consentGivenAt = null`) tant que le candidat ne
        // l'a pas donné explicitement. `null` = pas de consentement / consentement retiré.
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $consentGivenAt = null,
        #[ORM\Column(enumType: ConsentSource::class, nullable: true)]
        public ?ConsentSource $consentSource = null,
    ) {
        parent::__construct();
        $this->salaryExpected = new MonetaryAmount();
        $this->salaryMin = new MonetaryAmount();
        $this->presencePeriods = new ArrayCollection();
        $this->sectorExperiences = new ArrayCollection();
        $this->jobExperiences = new ArrayCollection();
        $this->jobTitles = new ArrayCollection();
        $this->languages = new ArrayCollection();
    }

    public function getPerson(): Person
    {
        return $this->person;
    }

    /** Consentement showcase-recruiter-webapp-saas explicite en cours de validité (RGPD, tâche 033). */
    public function hasConsent(): bool
    {
        return null !== $this->consentGivenAt;
    }

    public function giveConsent(ConsentSource $source, DateTimeImmutable $at): void
    {
        $this->consentGivenAt = $at;
        $this->consentSource = $source;
    }

    public function withdrawConsent(): void
    {
        $this->consentGivenAt = null;
        $this->consentSource = null;
    }

    public function getJarvi(): ?CandidateJarvi
    {
        return $this->jarvi;
    }

    public function setJarvi(CandidateJarvi $jarvi): void
    {
        $this->jarvi = $jarvi;
    }

    /** @return Collection<int, PresencePeriod> */
    public function getPresencePeriods(): Collection
    {
        return $this->presencePeriods;
    }

    /** @return Collection<int, CandidateSectorExperience> */
    public function getSectorExperiences(): Collection
    {
        return $this->sectorExperiences;
    }

    /** @return Collection<int, CandidateJobExperience> */
    public function getJobExperiences(): Collection
    {
        return $this->jobExperiences;
    }

    /** @return Collection<int, CandidateJobTitle> */
    public function getJobTitles(): Collection
    {
        return $this->jobTitles;
    }

    /** @return Collection<int, CandidateLanguage> */
    public function getLanguages(): Collection
    {
        return $this->languages;
    }
}
