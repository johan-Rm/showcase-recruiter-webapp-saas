<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\Candidate;
use App\Entity\VocabularyConcept;
use App\Enum\CurrentPositionCertainty;
use App\Enum\EducationLevel;
use App\Enum\JobTenure;
use App\Enum\LanguageProficiency;
use App\Enum\MatchField;
use App\Enum\Sector;
use App\Enum\VocabularyStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dit, pour un profil, lesquels des critères d'une recherche il satisfait — et par quoi.
 *
 * L'API filtrait sans jamais expliquer. Tant que les critères se cumulaient en ET, la question ne se
 * posait pas : tout membre du jeu les satisfaisait tous. Elle devient centrale dès que les mots-clés
 * se relient en OU — un profil peut alors entrer en n'en satisfaisant qu'un sur quatre, et le
 * recruteur voit un résultat qui gonfle sans comprendre pourquoi.
 *
 * ⚠️ CE FICHIER DOIT S'ACCORDER AVEC `CandidateRepository::applyFilters`. Ce sont deux lectures de
 * la même règle — l'une en SQL pour restreindre, l'autre en PHP pour expliquer — et deux lectures
 * finissent par diverger. Le garde-fou est dans les tests : un profil retenu par le filtre doit être
 * rapporté comme satisfaisant ce critère. Toute facette ajoutée au filtre s'ajoute ici.
 *
 * Les facettes inconnues sont ignorées, comme le fait le traducteur de critères : une recherche
 * ancienne ne doit pas casser après une évolution du moteur.
 */
final class CriterionMatcher
{
    /**
     * Termes de poste déjà résolus en concepts, pour la durée de la requête.
     *
     * Ce n'est pas de l'état métier mais un cache de résolution, et il n'est pas négociable :
     * marcher les libellés d'un concept profil par profil déclenche un chargement paresseux par
     * ligne — un N+1 sur une page de 200. Le filtre SQL, lui, résout le terme une seule fois ; on
     * fait pareil.
     *
     * @var array<string, list<int>>
     */
    private array $conceptIdsByTerm = [];

    /**
     * Le seuil du repli sur l'intitulé écrit. Doit rester égal à celui de `CandidateRepository` :
     * expliquer selon une règle et filtrer selon une autre est exactement la divergence contre
     * laquelle l'avertissement en tête de ce fichier met en garde.
     */
    private const int MIN_TITLE_FALLBACK_LENGTH = 5;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Charge d'avance les postes écrits de toute une page, en UNE requête.
     *
     * Même motif que le cache de concepts ci-dessus, et même conséquence si on l'oublie : expliquer
     * un mot-clé lit les postes du profil, et une collection non chargée déclenche une requête PAR
     * LIGNE — un N+1 sur une page entière, alors que la carte est justement servie par une requête
     * qui peint la page.
     *
     * Les entités de la page étant déjà gérées par l'ORM, cette requête ne les recharge pas : elle
     * remplit leur collection au passage.
     *
     * @param list<Candidate> $candidates
     */
    public function warmUp(array $candidates): void
    {
        if ([] === $candidates) {
            return;
        }

        $this->em->createQuery(
            'SELECT c, jt FROM '.Candidate::class.' c LEFT JOIN c.jobTitles jt WHERE c IN (:candidates)',
        )
            ->setParameter('candidates', $candidates)
            ->getResult();

        // LES MÉTIERS EXERCÉS, dans la même logique : expliquer un critère de poste les parcourt, et
        // sans cette seconde requête chaque profil rechargerait la sienne. Le défaut ne se voyait pas
        // tant qu'on expliquait une page de trente ; sur un plan de travail entier — un millier de
        // profils, autant de collections — il coûte des secondes.
        //
        // Le concept est chargé AVEC l'expérience : c'est lui qu'on compare, et lui dont on lit le
        // libellé pour dire « votre critère correspond à son poste ».
        $this->em->createQuery(
            'SELECT c, je, concept FROM '.Candidate::class.' c'
            .' LEFT JOIN c.jobExperiences je LEFT JOIN je.concept concept WHERE c IN (:candidates)',
        )
            ->setParameter('candidates', $candidates)
            ->getResult();
    }

    /**
     * @param list<array{facet: string, value: string, group: string}> $criteria
     *
     * @return list<CriterionMatch>
     */
    public function match(Candidate $candidate, array $criteria, ?DateTimeImmutable $at = null): array
    {
        $today = $at ?? new DateTimeImmutable('today');
        $matches = [];

        foreach ($criteria as $criterion) {
            $facet = $criterion['facet'];
            $value = trim($criterion['value']);
            if ('' === $value) {
                continue;
            }

            $matches[] = $this->one($candidate, $facet, $value, $today);
        }

        return array_values(array_filter($matches, static fn (?CriterionMatch $m): bool => null !== $m));
    }

    private function one(Candidate $candidate, string $facet, string $value, DateTimeImmutable $today): ?CriterionMatch
    {
        $hit = static fn (bool $matched, ?string $on = null): CriterionMatch => new CriterionMatch($facet, $value, $matched, $matched ? $on : null);
        $person = $candidate->getPerson();

        return match ($facet) {
            // Le mot-clé ne passe pas par `$hit` : c'est la seule facette qui porte un « où ».
            'q' => $this->keyword($candidate, $facet, $value),
            // Le métier non plus : c'est la seule qui porte un « quand ».
            'job' => $this->job($candidate, $facet, $value),
            // « au moins ce niveau », comme le filtre — et les profils sans niveau reconnu ne sont
            // pas supposés qualifiés.
            'educationLevel' => $hit(
                null !== $candidate->educationLevel
                && null !== EducationLevel::tryFrom($value)
                && \in_array($candidate->educationLevel, EducationLevel::atLeast(EducationLevel::from($value)), true),
                $candidate->educationLevel?->value,
            ),
            // « Au moins tant d'années », comme le filtre. Un profil dont l'expérience n'a pas pu
            // être dérivée porte 0 : il ne satisfait aucun minimum, on ne lui en prête pas.
            'experienceMin' => $hit(
                ($years = $candidate->getJarvi()->experienceYears) >= (int) $value && $years > 0,
                (string) $years,
            ),
            'availability' => $hit($candidate->availability?->value === $value, $candidate->availability?->value),
            'returnHorizon' => $hit($candidate->returnHorizon?->value === $value, $candidate->returnHorizon?->value),
            'geoMobility' => $hit($candidate->geoMobility === self::asBool($value)),
            'hasPiedATerre' => $hit($candidate->hasPiedATerre === self::asBool($value), $candidate->piedATerreCity),
            'star' => $hit((null !== $candidate->starInterviewedAt) === self::asBool($value)),
            'country' => $hit(mb_strtoupper((string) $person->country) === mb_strtoupper($value), $person->country),
            'sector' => $hit(self::sameText($candidate->desiredSector, $value), $candidate->desiredSector),
            'function' => $hit(self::sameText($candidate->desiredFunction, $value), $candidate->desiredFunction),
            'experienceSector' => $hit(...$this->experienceSector($candidate, $value)),
            'language' => $hit(...$this->language($candidate, $value, null)),
            'presenceCity' => $hit(...$this->presenceCity($candidate, $value)),
            'presenceWithinDays' => $hit(...$this->presenceWindow($candidate, $value, $today)),
            // `languageLevel` ne vaut rien seul : il précise la facette `language`, qui porte déjà
            // le niveau atteint. L'exposer deux fois dirait deux fois la même correspondance.
            default => null,
        };
    }

    /**
     * LE MOT-CLÉ CHERCHE LÀ OÙ LE FILTRE CHERCHE, ET DANS LE MÊME ORDRE. C'est un invariant, pas une
     * coïncidence : le filtre ramène le profil, cette méthode dit pourquoi. Les élargir séparément
     * produit l'écran le plus déroutant qui soit — un profil rendu par la recherche, dont la colonne
     * du verdict annonce que le critère n'est pas satisfait. Toute retouche ici va de pair avec
     * `CandidateRepository::applyFilters`.
     *
     * LE PREMIER QUI RÉPOND GAGNE, dans l'ordre où l'on lit une fiche : accroche, compétences,
     * postes écrits. Un profil qui correspond à plusieurs endroits n'en annonce qu'un —
     * deux provenances pour un même mot n'aident pas à décider.
     *
     * Les postes sont lus dans leur PROJECTION, jamais dans la structure brute du parcours : celle-ci
     * sérialise aussi le nom de ses rubriques, et « poste » y ramènerait tout le vivier.
     */
    private function keyword(Candidate $candidate, string $facet, string $value): CriterionMatch
    {
        $needle = mb_strtolower($value);
        $jarvi = $candidate->getJarvi();
        $found = static fn (MatchField $field, string $on): CriterionMatch => new CriterionMatch(
            $facet,
            $value,
            true,
            $on,
            null,
            $field,
        );

        if (null !== $jarvi->headline && str_contains(mb_strtolower($jarvi->headline), $needle)) {
            return $found(MatchField::HEADLINE, $jarvi->headline);
        }

        foreach ($jarvi->skills as $skill) {
            if (str_contains(mb_strtolower($skill), $needle)) {
                return $found(MatchField::SKILL, $skill);
            }
        }

        foreach ($candidate->getJobTitles() as $jobTitle) {
            // Le filtre ignore les intitulés retirés ; les ignorer ici aussi, explicitement, plutôt
            // que de dépendre d'un filtre global dont la présence changerait la réponse.
            if (null === $jobTitle->getDeletedAt() && str_contains(mb_strtolower($jobTitle->title), $needle)) {
                return $found(MatchField::JOB_TITLE, $jobTitle->title);
            }
        }

        return new CriterionMatch($facet, $value, false);
    }

    /**
     * Le poste EXERCÉ, résolu par le dictionnaire : le terme cherché vaut le libellé canonique du
     * concept ou l'un de ses libellés vivants — les dépréciés ne résolvent plus.
     *
     * Rapporte le libellé canonique DU CONCEPT exercé, celui que la fiche affiche, et — depuis
     * `API-026` — si ce métier est encore exercé.
     */
    private function job(Candidate $candidate, string $facet, string $value): CriterionMatch
    {
        // Le dictionnaire d'abord ; il connaît le terme ou il ne le connaît pas, et cette question
        // seule décide de la suite — exactement comme dans le filtre.
        if ([] !== $this->conceptIdsFor($value)) {
            $exercised = $this->exercisedConcept($candidate, $value);

            return new CriterionMatch(
                $facet,
                $value,
                null !== $exercised,
                $exercised?->prefLabel,
                null === $exercised ? null : $this->tenure($candidate, $exercised),
            );
        }

        // LE FILET (task API-037) : sans concept, le profil n'a pu entrer que par un intitulé écrit.
        // On rend CET intitulé, pas le terme cherché — le recruteur doit voir ce que la personne a
        // réellement écrit, qui est presque toujours plus long que ce qu'il a tapé. Aucune ancienneté
        // : un intitulé brut ne porte ni durée ni période, et en inventer une serait mentir.
        $title = self::literalTitle($candidate, $value);

        return new CriterionMatch($facet, $value, null !== $title, $title);
    }

    /** Le concept exercé qui satisfait ce terme, ou `null` si le profil n'en porte aucun. */
    private function exercisedConcept(Candidate $candidate, string $value): ?VocabularyConcept
    {
        $conceptIds = $this->conceptIdsFor($value);
        if ([] === $conceptIds) {
            return null;
        }

        foreach ($candidate->getJobExperiences() as $experience) {
            if (\in_array((int) $experience->concept->getId(), $conceptIds, true)) {
                return $experience->concept;
            }
        }

        return null;
    }

    /**
     * L'intitulé écrit du parcours qui contient ce terme — la même comparaison que le filtre, et le
     * même seuil : sous cinq caractères le repli ne se déclenche pas, ici comme là-bas.
     */
    private static function literalTitle(Candidate $candidate, string $value): ?string
    {
        $needle = mb_strtolower($value);
        if (mb_strlen($needle) < self::MIN_TITLE_FALLBACK_LENGTH) {
            return null;
        }

        foreach ($candidate->getJobTitles() as $jobTitle) {
            if (str_contains(mb_strtolower($jobTitle->title), $needle)) {
                return $jobTitle->title;
            }
        }

        return null;
    }

    /**
     * Ce métier reconnu est-il encore exercé — et si non, le sait-on (task API-026) ?
     *
     * Le critère de poste repose sur un CUMUL DE CARRIÈRE : la ligne dit qu'une personne a exercé ce
     * métier, sans dire quand. Mesuré sur le vivier réel, parmi les 293 profils ayant exercé
     * « Développeur logiciel », 90 le font encore, 92 sont passés à autre chose, et **111 ne disent
     * rien**. Le recruteur trie ces trois populations différemment, et l'écran les montrait à
     * l'identique.
     *
     * TROIS RÈGLES, et chacune protège d'un mensonge :
     *
     * - le poste du moment doit être **CERTAIN** — 410 profils en portent un marqué ambigu, où
     *   plusieurs postes se disputent le rôle. Le doute y est déjà mesuré ; l'ignorer afficherait
     *   « actuellement » sur des profils dont on sait qu'on n'est pas sûr ;
     * - `PAST` exige la **preuve d'un autre poste** occupé aujourd'hui. Le déduire d'une absence
     *   rangerait les 111 inconnus avec les 92 qui ont réellement bougé ;
     * - sans poste actuel connu, on rend `null` : rien ne s'affiche, rien n'est affirmé.
     *
     * AUCUNE REQUÊTE SUPPLÉMENTAIRE. La réplica est déjà chargée pour ce profil — `experienceMin` la
     * lit —, et du concept actuel on ne demande que l'IDENTIFIANT, qu'un proxy Doctrine porte sans
     * aller le chercher. Comparer les LIBELLÉS aurait déclenché un chargement par ligne : un N+1 sur
     * une page de deux cents.
     */
    private function tenure(Candidate $candidate, VocabularyConcept $exercised): ?JobTenure
    {
        // La réplica peut manquer — un profil sans elle n'a pas de poste actuel, et c'est un silence,
        // pas une erreur. Un poste actuel non nul prouve ensuite que la réplica ne l'était pas.
        $jarvi = $candidate->getJarvi();
        $current = $jarvi?->currentJobConcept;

        if (null === $current || CurrentPositionCertainty::CERTAIN !== $jarvi->currentPositionCertainty) {
            return null;
        }

        return $current->getId() === $exercised->getId() ? JobTenure::CURRENT : JobTenure::PAST;
    }

    /**
     * Les concepts qu'un terme désigne — par libellé canonique ou par libellé VIVANT, les dépréciés
     * ne résolvant plus. Même résolution que le filtre, en une requête, mémorisée pour la suite du
     * jeu de résultats.
     *
     * @return list<int>
     */
    private function conceptIdsFor(string $term): array
    {
        $key = mb_strtolower($term);

        if (!isset($this->conceptIdsByTerm[$key])) {
            /** @var list<array{id: int|string}> $rows */
            $rows = $this->em->createQuery(
                'SELECT DISTINCT c.id FROM '.VocabularyConcept::class.' c '
                .'LEFT JOIN c.labels l WITH l.status != :deprecated '
                .'WHERE LOWER(c.prefLabel) = :term OR LOWER(l.label) = :term'
            )
                ->setParameter('term', $key)
                ->setParameter('deprecated', VocabularyStatus::DEPRECIE)
                ->getResult();

            $this->conceptIdsByTerm[$key] = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        }

        return $this->conceptIdsByTerm[$key];
    }

    /** @return array{0: bool, 1: ?string} */
    private function experienceSector(Candidate $candidate, string $value): array
    {
        $sector = Sector::tryFrom($value);

        foreach ($candidate->getSectorExperiences() as $experience) {
            if ($experience->sector === $sector) {
                return [true, $experience->sector->value];
            }
        }

        return [false, null];
    }

    /**
     * La langue parlée, et le niveau atteint quand il est connu — le filtre exclut les entrées sans
     * niveau dès qu'un minimum est exigé, on ne suppose donc jamais un niveau absent.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function language(Candidate $candidate, string $value, ?LanguageProficiency $minimum): array
    {
        $wanted = mb_strtolower($value);

        foreach ($candidate->getLanguages() as $spoken) {
            if (mb_strtolower($spoken->language) !== $wanted) {
                continue;
            }
            if (null !== $minimum && null === $spoken->proficiency) {
                continue;
            }

            return [true, $spoken->proficiency->value ?? $spoken->language];
        }

        return [false, null];
    }

    /** @return array{0: bool, 1: ?string} */
    private function presenceCity(Candidate $candidate, string $value): array
    {
        $wanted = mb_strtolower($value);

        foreach ($candidate->getPresencePeriods() as $period) {
            if (mb_strtolower((string) $period->city) === $wanted) {
                return [true, $period->city];
            }
        }

        return [false, null];
    }

    /**
     * Un séjour qui chevauche les N prochains jours — la règle du filtre, au jour près.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function presenceWindow(Candidate $candidate, string $value, DateTimeImmutable $today): array
    {
        $days = filter_var($value, \FILTER_VALIDATE_INT);
        if (false === $days || $days <= 0) {
            return [false, null];
        }

        $until = $today->modify(\sprintf('+%d days', $days));

        foreach ($candidate->getPresencePeriods() as $period) {
            if ($period->endDate >= $today && $period->startDate <= $until) {
                return [true, $period->startDate->format('Y-m-d')];
            }
        }

        return [false, null];
    }

    private static function sameText(?string $actual, string $value): bool
    {
        return null !== $actual && mb_strtolower($actual) === mb_strtolower($value);
    }

    /** Même lecture que le traducteur de critères : « true » et « 1 » valent vrai, le reste faux. */
    private static function asBool(string $value): bool
    {
        return \in_array(mb_strtolower($value), ['1', 'true'], true);
    }
}
