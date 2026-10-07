<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Search\Demo\DemoSearchProgress;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une carte de démonstration a le droit d'annoncer — task API-029.
 *
 * Les trois nombres ne sont pas indépendants : une grille où l'on aurait retenu des profils jamais
 * ouverts, ou tout vu d'un jeu dont une partie vient d'arriver, ne dit rien de ce qu'un recruteur
 * verra. Ce sont ces relations que le test tient.
 */
final class DemoSearchProgressTest extends TestCase
{
    /** Le cas nominal : la part parcourue se prend sur ce qui était déjà là. */
    public function testFreshProfilesAreExcludedFromWhatCountsAsSeen(): void
    {
        $shares = (new DemoSearchProgress(seenPercent: 100, retained: 3, fresh: 2))->sharesFor(22);

        self::assertSame(['fresh' => 2, 'seen' => 20, 'retained' => 3], $shares);
    }

    /** On ne met pas de côté un profil qu'on n'a pas ouvert. */
    public function testRetainedNeverExceedsSeen(): void
    {
        $shares = (new DemoSearchProgress(seenPercent: 6, retained: 14, fresh: 0))->sharesFor(10);

        self::assertSame(1, $shares['seen']);
        self::assertSame(1, $shares['retained']);
    }

    /** Un jeu plus petit que l'intention la ramène à sa taille, sans jamais la dépasser. */
    public function testASmallResultSetBoundsEveryShare(): void
    {
        $shares = (new DemoSearchProgress(seenPercent: 78, retained: 5, fresh: 27))->sharesFor(3);

        self::assertSame(3, $shares['fresh']);
        self::assertSame(0, $shares['seen']);
        self::assertSame(0, $shares['retained']);
    }

    /** Une recherche sans résultat n'annonce rien : c'est la carte du brouillon. */
    public function testAnEmptyResultSetAnnouncesNothing(): void
    {
        $shares = (new DemoSearchProgress(seenPercent: 55, retained: 14, fresh: 12))->sharesFor(0);

        self::assertSame(['fresh' => 0, 'seen' => 0, 'retained' => 0], $shares);
    }
}
