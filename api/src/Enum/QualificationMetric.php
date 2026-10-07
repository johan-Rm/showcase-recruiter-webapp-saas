<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Les qualifications dérivées dont on sait expliquer l'absence (task API-034).
 *
 * Sous-ensemble des métriques de {@see \App\Domain\Reporting\QualificationReporter} : seules
 * celles dont l'inverse désigne un vrai manque à examiner. Une métrique de répartition — la
 * séniorité, la certitude du poste actuel — n'a pas sa place ici : « qui n'est pas SENIOR » n'est
 * pas un défaut.
 *
 * La valeur est l'identifiant d'URL de l'opération de diagnostic.
 */
enum QualificationMetric: string
{
    case EXPERIENCE_YEARS = 'experienceYears';
    case CURRENT_ROLE_MONTHS = 'currentRoleMonths';
    case EDUCATION_LEVEL = 'educationLevel';
    case JARVI_UPDATED_AT = 'jarviUpdatedAt';

    /**
     * Les motifs que cette métrique peut produire, et eux seuls. Un motif n'a de sens que rapporté
     * à sa métrique : filtrer l'ancienneté sur `NO_EDUCATION` est une erreur d'appel, pas un
     * résultat vide.
     *
     * @return list<QualificationGapReason>
     */
    public function reasons(): array
    {
        return match ($this) {
            self::EXPERIENCE_YEARS => [
                QualificationGapReason::NO_POSITION,
                QualificationGapReason::NO_USABLE_START_DATE,
                QualificationGapReason::INCOHERENT_DATES,
                QualificationGapReason::UNDER_ONE_YEAR,
            ],
            self::CURRENT_ROLE_MONTHS => [
                QualificationGapReason::NO_ONGOING_POSITION,
                QualificationGapReason::ONGOING_WITHOUT_START_DATE,
                QualificationGapReason::UNDER_ONE_MONTH,
            ],
            self::EDUCATION_LEVEL => [
                QualificationGapReason::NO_EDUCATION,
                QualificationGapReason::UNRECOGNIZED_TITLE,
            ],
            self::JARVI_UPDATED_AT => [
                QualificationGapReason::SOURCE_WITHOUT_DATE,
            ],
        };
    }
}
