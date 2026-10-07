<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\CandidateItemProvider;
use DateTimeImmutable;

/**
 * Profil candidat détaillé, réplica pro Jarvi incluse (résumé, expériences, formations, langues).
 *
 * Masqués tant que l'entreprise du recruteur n'a pas dévoilé le candidat (`revealed`) : les
 * coordonnées (contactEmail, phone, whatsappNumber, linkedinUrl), la photo, le prénom et le nom
 * (initiale seule), les métadonnées CV (`resumes`), le compte rendu d'entretien — et les
 * **employeurs** du parcours (`workExperiences[].companyName` à `null`). Les intitulés de poste et les périodes, eux, restent
 * visibles : ils qualifient sans nommer. Cf. cadrage recherche_et_masquage.md § Ré-identification.
 */
#[ApiResource(
    shortName: 'Candidate',
    operations: [
        new Get(
            uriTemplate: '/candidates/{id}',
            uriVariables: ['id'],
            provider: CandidateItemProvider::class,
        ),
    ],
)]
final class CandidateOutput
{
    /**
     * @param list<string>                                                               $skills
     * @param list<array<string, mixed>>                                                 $languages
     * @param list<array<string, mixed>>                                                 $workExperiences
     * @param list<array<string, mixed>>                                                 $educations
     * @param list<array<string, mixed>>|null                                            $resumes
     * @param list<array{startDate: string, endDate: string, city: ?string}>             $presencePeriods
     * @param list<array{job: string, months: int, positions: int, exactPositions: int}> $jobExperiences
     * @param list<array{sector: string, months: int}>                                   $sectorExperiences
     * @param list<array{language: string, proficiency: ?string}>                        $spokenLanguages
     */
    public function __construct(
        public string $id,
        public string $givenName,
        public ?string $familyName,
        /** Photo : URL publique Jarvi servie telle quelle (le front dimensionne) ; `null` si masqué. */
        public ?string $photoUrl,
        public ?string $headline,
        public ?string $summary,
        /** Années d'expérience dérivées du parcours à l'ingestion — visibles même masqué (agrégat). */
        public int $experienceYears,
        /**
         * Poste ACTUEL dérivé à l'ingestion (task 117). L'intitulé et la certitude décrivent sans
         * nommer, donc visibles même masqué ; l'employeur ne sort qu'au dévoilement.
         *
         * `currentPositionCertainty` vaut CERTAIN, AMBIGUOUS (plusieurs postes en cours dans des
         * entreprises différentes — celui exposé n'est pas garanti principal) ou ABSENT (aucun poste
         * en cours, à ne pas confondre avec un poste inconnu).
         */
        public string $currentPositionCertainty,
        public ?string $currentTitle,
        public ?string $currentCompanyName,
        /** Métier reconnu dans l'intitulé du poste actuel — `null` si aucun, ou si ce n'est qu'un statut. */
        public ?string $currentJob,
        public ?string $city,
        public ?string $country,
        public ?string $contactEmail,
        public ?string $phone,
        /** Numéro joignable sur WhatsApp (E.164), dérivé du JID Jarvi — `null` si masqué. */
        public ?string $whatsappNumber,
        public ?string $linkedinUrl,
        /** Joignabilité : la PRÉSENCE d'une coordonnée, exposée même masqué — jamais sa valeur. */
        public bool $hasEmail,
        public bool $hasPhone,
        /**
         * Le CV et le compte rendu disent eux aussi leur présence (task `API-064`), et rien de plus :
         * leur contenu reste derrière son dévoilement. Le CV compte s'il porte un FICHIER, le compte
         * rendu s'il a un TEXTE — les règles vivent dans `Domain\Candidate\RevealableContent`.
         */
        public bool $hasResume,
        public bool $hasReport,
        /**
         * Dernier contact AVEC le candidat et son canal (task 121) — exposés MÊME masqué : ils
         * disent qu'un échange a eu lieu, jamais l'adresse utilisée (même logique que
         * `hasEmail`/`hasPhone`). C'est avant de dépenser un dévoilement qu'un recruteur a besoin
         * de savoir qu'un confrère est déjà passé.
         *
         * Vide sur ~94 % du vivier, et c'est exact : ces candidats n'ont jamais été approchés.
         * Ne PAS lire comme une fraîcheur de profil.
         */
        public ?DateTimeImmutable $lastInteractionAt,
        public ?string $lastInteractionType,
        public ?string $desiredSector,
        public ?string $desiredFunction,
        public ?string $availability,
        public ?string $returnHorizon,
        public bool $geoMobility,
        public array $skills,
        public array $languages,
        /**
         * Métiers EXERCÉS et durée cumulée, dérivés du parcours (task 095). Visibles même masqué :
         * ils décrivent sans nommer, comme les années d'expérience.
         *
         * **`months` est indicatif** — le rattachement des intitulés est partiel (62 % du parcours
         * médian), donc la durée sous-estime. Le signal fiable est la PRÉSENCE du métier.
         * `positions` dit combien de postes ont alimenté la ligne, `exactPositions` combien ont été
         * reconnus à l'identique : sans eux, rien ne distingue un métier vu 5 fois d'un métier déduit
         * d'un seul rattachement partiel (faux dans ~7 % des cas, cf. `118`).
         *
         * Triés par durée décroissante — un profil peut en porter six.
         */
        public array $jobExperiences,
        /** Secteurs d'expérience et mois cumulés (task 095), triés par durée décroissante. */
        public array $sectorExperiences,
        /**
         * Langues NORMALISÉES (code ISO + niveau), issues de `candidate_language` — la seule source
         * filtrable. `languages` ci-dessus reste le JSON Jarvi brut, conservé pour compatibilité.
         * `proficiency` est nullable : le niveau manque sur un tiers des entrées, on ne le suppose pas.
         */
        public array $spokenLanguages,
        public array $workExperiences,
        public array $educations,
        /**
         * Niveau d'études le plus élevé, NORMALISÉ et dérivé des formations (task 112) — le signal
         * filtrable, à côté des `educations` brutes. `null` si aucun intitulé n'est reconnaissable
         * (≈ 23 % du vivier) : jamais un niveau supposé.
         */
        public ?string $educationLevel,
        /** Métadonnées CV (fileId, name, mimeType…) — null tant que non dévoilé. */
        public ?array $resumes,
        public array $presencePeriods,
        public bool $isStar,
        public ?DateTimeImmutable $starInterviewedAt,
        public ?string $starInterviewReport,
        /**
         * Ce que l'entreprise a PAYÉ sur ce profil — quatre accès indépendants (task `API-018`).
         *
         * `revealedEmail` et `revealedPhone` commandent chacun SON canal, et rien d'autre :
         * l'identité, l'employeur et le profil LinkedIn se servent sans crédit. `revealedResume`
         * ouvre le CV, `revealedReport` le compte rendu d'entretien, tous deux servis sur la fiche.
         */
        public bool $revealedEmail,
        public bool $revealedPhone,
        public bool $revealedResume,
        public bool $revealedReport,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
