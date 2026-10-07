<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Reconnaît le concept métier porté par un intitulé de poste (task 118).
 *
 * L'égalité exacte ne rattachait que **26,6 %** des postes du vivier, alors que **56 % des intitulés
 * restants contenaient déjà un libellé connu** : le frein n'était pas la taille du dictionnaire mais
 * la façon de comparer. La reconnaissance partielle porte le rattachement à **67,7 %**.
 *
 * Trois règles la rendent défendable, dans cet ordre :
 *
 * 1. **Un mot entier, jamais une sous-chaîne** — « ingénieur » ne doit pas se déclencher sur
 *    « ingénierie ».
 * 2. **Le métier bat le statut** — « stage auditeur interne » est un auditeur, pas un stagiaire.
 *    Sans cette règle, « Stage / Internship », concept de poste le plus fréquent du vivier,
 *    raflerait tout ce qui contient « stage ».
 * 3. **Le plus spécifique gagne** — d'abord le libellé le plus long en mots, puis le plus RARE.
 *    Les termes génériques (« manager », « ingénieur ») sont les plus fréquents ; les départager par
 *    rareté évite qu'ils avalent un intitulé précis.
 *
 * Le prix est connu et mesuré : sur un échantillon relu à la main, ~7 % des rattachements partiels
 * sont faux et ~27 % appauvrissent le sens sans le trahir. Acceptable pour ÉLARGIR une recherche,
 * d'où {@see ResolvedJob::$exact} — un consommateur qui exige de la précision peut s'y limiter.
 */
final readonly class TitleConceptResolver
{
    public function __construct(private JobConceptCatalog $concepts)
    {
    }

    /** @param string $canonicalTitle intitulé déjà passé par TermNormalizer::canonicalizeTitle() */
    public function resolve(string $canonicalTitle): ?ResolvedJob
    {
        if ('' === $canonicalTitle) {
            return null;
        }

        $exact = $this->concepts->byTerm()[$canonicalTitle] ?? null;
        if ($exact instanceof JobConceptEntry) {
            return new ResolvedJob($exact->conceptId, $exact->isStatus, true);
        }

        $matches = [];
        foreach ($this->concepts->candidatesFor(explode(' ', $canonicalTitle)) as $entry) {
            if (self::containsWholeTerm($canonicalTitle, $entry->term)) {
                $matches[] = $entry;
            }
        }
        if ([] === $matches) {
            return null;
        }

        $jobs = array_values(array_filter($matches, static fn (JobConceptEntry $e): bool => !$e->isStatus));
        $best = self::mostSpecific([] !== $jobs ? $jobs : $matches);

        return new ResolvedJob($best->conceptId, $best->isStatus, false);
    }

    /** @param list<JobConceptEntry> $entries */
    private static function mostSpecific(array $entries): JobConceptEntry
    {
        usort($entries, static fn (JobConceptEntry $a, JobConceptEntry $b): int => [$b->words, $a->frequency] <=> [$a->words, $b->frequency]);

        return $entries[0];
    }

    private static function containsWholeTerm(string $title, string $term): bool
    {
        return 1 === preg_match('#(?<!\w)'.preg_quote($term, '#').'(?!\w)#u', $title);
    }
}
