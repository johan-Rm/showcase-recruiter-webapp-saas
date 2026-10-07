<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\ResolvedJob;
use App\Domain\Vocabulary\TermNormalizer;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Enum\CurrentPositionCertainty;
use DateTimeImmutable;
use Exception;

/**
 * Poste ACTUEL d'un candidat, dérivé des `positions` Jarvi à l'ingestion (task 117).
 *
 * Définition UNIQUE du poste actuel — elle vivait auparavant dans un calculateur qui n'en tirait
 * qu'une durée, sans jamais dire de quel poste il s'agissait. Deux définitions concurrentes du même
 * concept sont exactement ce que `094a` a corrigé ailleurs.
 *
 * Le poste actuel est la position **en cours** (sans `endDate`) commencée le plus récemment. Mesuré
 * sur le vivier, cette règle ne tranche que 53 % des profils, d'où la certitude portée explicitement :
 *
 * - une seule position en cours, ou plusieurs dans la MÊME entreprise → CERTAIN (promotion, ligne
 *   non clôturée : le départage par date la plus récente y est juste) ;
 * - plusieurs entreprises différentes → AMBIGUOUS, la plus récente est exposée sans garantie qu'il
 *   s'agisse de l'emploi principal — départager demanderait un signal qu'on n'a pas ;
 * - aucune → ABSENT, jamais confondu avec un poste vide.
 */
final class CurrentPositionExtractor
{
    private const int MONTHS_PER_YEAR = 12;

    /** @param list<array<string, mixed>> $positions `positions` Jarvi */
    public static function fromPositions(
        array $positions,
        TitleConceptResolver $resolver,
        ?DateTimeImmutable $now = null,
    ): CurrentPosition {
        $now ??= new DateTimeImmutable();
        $normalizer = new TermNormalizer();

        $ongoing = array_values(array_filter(
            $positions,
            static fn (array $position): bool => null === ($position['endDate'] ?? null),
        ));
        if ([] === $ongoing) {
            return CurrentPosition::absent();
        }

        $companies = [];
        foreach ($ongoing as $position) {
            $companies[$normalizer->normalize((string) ($position['companyName'] ?? ''))] = true;
        }
        $certainty = \count($companies) > 1 ? CurrentPositionCertainty::AMBIGUOUS : CurrentPositionCertainty::CERTAIN;

        $current = self::mostRecent($ongoing, $now);
        $title = trim((string) ($current['title'] ?? ''));
        $job = $resolver->resolve($normalizer->canonicalizeTitle($title));

        return new CurrentPosition(
            $certainty,
            '' !== $title ? $title : null,
            self::nullableString($current['companyName'] ?? null),
            $job instanceof ResolvedJob && !$job->isStatus ? $job->conceptId : null,
            self::months(self::date($current['startDate'] ?? null), $now),
        );
    }

    /**
     * @param non-empty-list<array<string, mixed>> $ongoing
     *
     * @return array<string, mixed>
     */
    private static function mostRecent(array $ongoing, DateTimeImmutable $now): array
    {
        $best = $ongoing[0];
        $bestStart = self::date($best['startDate'] ?? null);
        foreach (\array_slice($ongoing, 1) as $position) {
            $start = self::date($position['startDate'] ?? null);
            if (!$start instanceof DateTimeImmutable || $start > $now) {
                continue;   // sans date exploitable, la position ne peut pas revendiquer la plus récente
            }
            if (!$bestStart instanceof DateTimeImmutable || $start > $bestStart) {
                [$best, $bestStart] = [$position, $start];
            }
        }

        return $best;
    }

    private static function months(?DateTimeImmutable $start, DateTimeImmutable $now): int
    {
        if (!$start instanceof DateTimeImmutable || $start > $now) {
            return 0;   // date de début absente : on ne suppose pas une durée
        }

        $diff = $start->diff($now);

        return ($diff->y * self::MONTHS_PER_YEAR) + $diff->m;
    }

    private static function nullableString(mixed $value): ?string
    {
        $string = trim(\is_string($value) ? $value : '');

        return '' !== $string ? $string : null;
    }

    private static function date(mixed $value): ?DateTimeImmutable
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
