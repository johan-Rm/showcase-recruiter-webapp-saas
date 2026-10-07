<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Enum\Availability;
use App\Enum\EducationLevel;
use App\Enum\LanguageProficiency;
use App\Enum\ReturnHorizon;
use App\Enum\Sector;
use App\Repository\CandidateSearchCriteria;

/**
 * Traduit les critères stockés d'une recherche — liste ordonnée de `{facet, value, group}` — en
 * CandidateSearchCriteria, le DTO que consomme CandidateRepository::search.
 *
 * L'ordre porte le nom auto côté front, pas le sens du filtre : deux critères sur la même facette
 * ne s'additionnent pas en V1, le dernier gagne (le moteur n'a qu'une valeur par facette).
 * Une facette inconnue est ignorée plutôt que de faire échouer la recherche : les critères sont
 * de la donnée libre, une recherche ancienne ne doit pas casser après une évolution du moteur.
 */
final class SearchCriteriaTranslator
{
    /** Facettes booléennes : « true » / « 1 » valent vrai, tout le reste faux. */
    private const array BOOLEAN_FACETS = ['geoMobility', 'hasPiedATerre', 'star'];

    /**
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    public function toSearchCriteria(array $criteria, int $page = 1, int $perPage = 30): CandidateSearchCriteria
    {
        $values = [];
        $keywords = [];
        foreach ($criteria as $criterion) {
            $values[$criterion['facet']] = $criterion['value'];

            // Les mots-clés font exception au « dernier gagne » : ils s'ACCUMULENT et se relient en
            // OU. C'est la seule facette multivaluée — poser deux mots-clés élargit la recherche,
            // là où poser deux fois n'importe quel autre critère la remplace.
            if ('q' === $criterion['facet'] && '' !== trim($criterion['value'])) {
                $keywords[] = trim($criterion['value']);
            }
        }

        return new CandidateSearchCriteria(
            keywords: array_values(array_unique($keywords)),
            sector: self::string($values['sector'] ?? null),
            experienceSector: Sector::tryFrom($values['experienceSector'] ?? ''),
            job: self::string($values['job'] ?? null),
            experienceMin: self::positiveInt($values['experienceMin'] ?? null),
            language: self::string($values['language'] ?? null),
            languageLevel: LanguageProficiency::tryFrom($values['languageLevel'] ?? ''),
            function: self::string($values['function'] ?? null),
            availability: Availability::tryFrom($values['availability'] ?? ''),
            educationLevel: EducationLevel::tryFrom($values['educationLevel'] ?? ''),
            returnHorizon: ReturnHorizon::tryFrom($values['returnHorizon'] ?? ''),
            geoMobility: self::bool($values, 'geoMobility'),
            hasPiedATerre: self::bool($values, 'hasPiedATerre'),
            star: self::bool($values, 'star'),
            country: self::string($values['country'] ?? null),
            presenceWithinDays: self::positiveInt($values['presenceWithinDays'] ?? null),
            presenceCity: self::string($values['presenceCity'] ?? null),
            page: $page,
            perPage: $perPage,
        );
    }

    private static function string(?string $value): ?string
    {
        return null !== $value && '' !== $value ? $value : null;
    }

    /** @param array<string, string> $values */
    private static function bool(array $values, string $facet): ?bool
    {
        if (!\in_array($facet, self::BOOLEAN_FACETS, true) || !isset($values[$facet])) {
            return null;
        }

        return \in_array($values[$facet], ['1', 'true'], true);
    }

    private static function positiveInt(?string $value): ?int
    {
        if (null === $value || !ctype_digit($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
