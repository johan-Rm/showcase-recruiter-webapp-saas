<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Enum\QualificationGapReason;
use App\Enum\QualificationMetric;
use Doctrine\DBAL\Connection;

/**
 * Qui manque, et pourquoi (task API-034) : le pendant nominatif des compteurs de
 * {@see QualificationReporter}, qui dit combien sans jamais dire qui.
 *
 * LECTURE SEULE, SQL natif → la clause `deleted_at IS NULL` est portée par la requête (le soft
 * delete Doctrine ne couvre pas le SQL natif, cf. data-integrity.md).
 *
 * **Le prédicat de manque est l'exacte négation du compteur**, et il se filtre sur `candidate_jarvi`
 * SEUL — comme le compteur. Une jointure restrictive vers `candidate` ou `person` retirerait des
 * lignes que le compteur, lui, a comptées, et la somme des motifs cesserait d'égaler le manque
 * affiché à l'écran. D'où les jointures ouvertes ci-dessous : elles nomment quand elles peuvent,
 * elles n'excluent jamais.
 *
 * Le motif n'existant dans aucune colonne, ni `WHERE` ni `LIMIT` ne peuvent le trancher : le lot des
 * profils en manque est chargé en entier, puis classé. Le plus gros manque mesuré tient en quelques
 * centaines de profils, et seules les colonnes nécessaires au classement sont lues. Si un manque
 * venait à dépasser quelques milliers de profils, c'est cette décision qu'il faudrait rouvrir.
 */
final readonly class QualificationGapReporter
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * Les profils que la métrique ne compte pas, chacun avec son motif, triés par nom.
     *
     * @return list<array{id: string, givenName: string, familyName: string, city: ?string, country: ?string, reason: QualificationGapReason}>
     */
    public function gaps(QualificationMetric $metric): array
    {
        $rows = $this->connection->fetchAllAssociative(\sprintf(
            <<<'SQL'
                SELECT c.uuid AS id, p.given_name, p.family_name, p.city, p.country%s
                FROM candidate_jarvi cj
                LEFT JOIN candidate c ON c.id = cj.candidate_id
                LEFT JOIN person p ON p.id = c.person_id
                WHERE cj.deleted_at IS NULL AND %s
                ORDER BY p.family_name, p.given_name, cj.id
                SQL,
            self::payloadColumns($metric),
            self::gapPredicate($metric),
        ));

        return array_map(
            static fn (array $row): array => [
                'id' => (string) $row['id'],
                'givenName' => (string) ($row['given_name'] ?? ''),
                'familyName' => (string) ($row['family_name'] ?? ''),
                'city' => self::nullableString($row['city'] ?? null),
                'country' => self::nullableString($row['country'] ?? null),
                'reason' => QualificationGapClassifier::classify(
                    $metric,
                    self::decode($row['work_experiences'] ?? null),
                    self::decode($row['educations'] ?? null),
                ),
            ],
            $rows,
        );
    }

    /**
     * Effectif par motif, du plus fourni au plus rare puis par code — un ordre total, sans quoi deux
     * lectures successives ne présentent pas la même chose. Toujours calculé sur le manque ENTIER :
     * sélectionner un motif ne doit pas faire bouger les compteurs des autres.
     *
     * @param list<array{reason: QualificationGapReason, ...}> $gaps
     *
     * @return list<array{reason: string, count: int}>
     */
    public static function breakdown(array $gaps): array
    {
        $counts = [];
        foreach ($gaps as $gap) {
            $code = $gap['reason']->value;
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }

        uksort($counts, static fn (string $a, string $b): int => [$counts[$b], $a] <=> [$counts[$a], $b]);

        return array_map(
            static fn (string $reason, int $count): array => ['reason' => $reason, 'count' => $count],
            array_keys($counts),
            array_values($counts),
        );
    }

    /** Les colonnes brutes que le classement d'une métrique doit relire, et rien de plus. */
    private static function payloadColumns(QualificationMetric $metric): string
    {
        return match ($metric) {
            QualificationMetric::EXPERIENCE_YEARS,
            QualificationMetric::CURRENT_ROLE_MONTHS => ', cj.work_experiences',
            QualificationMetric::EDUCATION_LEVEL => ', cj.educations',
            QualificationMetric::JARVI_UPDATED_AT => '',
        };
    }

    /** Négation exacte du compteur de `QualificationReporter::profileQualifications()`. */
    private static function gapPredicate(QualificationMetric $metric): string
    {
        return match ($metric) {
            QualificationMetric::EXPERIENCE_YEARS => 'cj.experience_years = 0',
            QualificationMetric::CURRENT_ROLE_MONTHS => 'cj.current_role_months = 0',
            QualificationMetric::EDUCATION_LEVEL => 'cj.education_level IS NULL',
            QualificationMetric::JARVI_UPDATED_AT => 'cj.jarvi_updated_at IS NULL',
        };
    }

    /** @return list<array<string, mixed>> */
    private static function decode(mixed $value): array
    {
        if (!\is_string($value) || '' === $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return \is_array($decoded) ? array_values(array_filter($decoded, \is_array(...))) : [];
    }

    private static function nullableString(mixed $value): ?string
    {
        $string = trim(\is_string($value) ? $value : '');

        return '' !== $string ? $string : null;
    }
}
