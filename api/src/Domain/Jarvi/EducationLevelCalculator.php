<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Enum\EducationLevel;

/**
 * Niveau d'études le plus élevé d'un parcours, dérivé des titres de formation Jarvi (task 112).
 *
 * Jarvi ne fournit pas ce niveau : il donne des intitulés libres et multilingues (« Master 2 (M2) »,
 * « Diplôme universitaire de technologie (DUT) », « Bachelor of Business Administration - BBA »…).
 * On les reconnaît par motifs, sur un texte normalisé (minuscules, accents dépliés).
 *
 * On retient le niveau le PLUS ÉLEVÉ du parcours, pas le plus récent : une personne qui reprend un
 * bac+2 après un master reste au master. Un intitulé illisible ne produit RIEN — jamais de niveau
 * par défaut, qui fausserait la statistique.
 */
final class EducationLevelCalculator
{
    /**
     * Ordre décisif : les motifs les plus spécifiques d'abord. `bac+2` doit être testé avant `bac`,
     * qui le contient ; `bachelor` avant `bac` pour la même raison (d'où la limite de mot sur `bac`).
     *
     * @var array<string, EducationLevel>
     */
    private const array PATTERNS = [
        '/doctorat|docteur|\bphd\b|doctorate/' => EducationLevel::DOCTORAT,
        '/ingenieur|engineering degree/' => EducationLevel::INGENIEUR,
        '/master|mastere|magistere|\bmba\b|\bm1\b|\bm2\b/' => EducationLevel::MASTER,
        '/licence|bachelor|\bbba\b/' => EducationLevel::LICENCE,
        '/\bdut\b|\bbts\b|\bdeug\b|bac\s*\+\s*2|technicien superieur/' => EducationLevel::BAC_PLUS_2,
        '/baccalaureat|\bbac\b/' => EducationLevel::BAC,
    ];

    /**
     * @param list<array<string, mixed>> $educations `educations` Jarvi (title, school, dates…)
     *
     * @return EducationLevel|null null si aucun intitulé n'est reconnaissable
     */
    public static function fromEducations(array $educations): ?EducationLevel
    {
        $highest = null;
        foreach ($educations as $education) {
            $level = self::levelOf((string) ($education['title'] ?? ''));
            if (null !== $level && (null === $highest || $level->rank() > $highest->rank())) {
                $highest = $level;
            }
        }

        return $highest;
    }

    private static function levelOf(string $title): ?EducationLevel
    {
        $normalized = self::normalize($title);
        if ('' === $normalized) {
            return null;
        }

        foreach (self::PATTERNS as $pattern => $level) {
            if (1 === preg_match($pattern, $normalized)) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Minuscules + accents dépliés : « Diplôme d'Ingénieur » et « diplome d ingenieur » doivent se
     * reconnaître pareil.
     */
    private static function normalize(string $title): string
    {
        $lowered = mb_strtolower(trim($title));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $lowered);

        return false !== $ascii ? $ascii : $lowered;
    }
}
