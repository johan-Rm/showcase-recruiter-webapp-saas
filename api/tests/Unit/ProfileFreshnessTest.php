<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Search\ProfileFreshness;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * « Nouveau » est un ÂGE, pas un repère de lecture (task `API-022`).
 *
 * Toute la règle tient dans une comparaison de dates, et c'est précisément ce qui la rend fragile :
 * une inégalité large plutôt que stricte fait clignoter le badge le septième jour, et personne ne le
 * verrait à l'écran sans attendre une semaine. D'où la date injectée, et d'où ces cas.
 */
final class ProfileFreshnessTest extends TestCase
{
    private const string NOW = '2026-08-03 14:00:00';

    #[DataProvider('entriesProvider')]
    public function testIsNew(string $enteredAt, bool $expected, string $because): void
    {
        self::assertSame(
            $expected,
            ProfileFreshness::isNew(new DateTimeImmutable($enteredAt), new DateTimeImmutable(self::NOW)),
            $because,
        );
    }

    /**
     * @return iterable<string, array{string, bool, string}>
     */
    public static function entriesProvider(): iterable
    {
        yield 'arrivé à l’instant' => [self::NOW, true, 'Un profil qui vient d’arriver est nouveau.'];
        yield 'arrivé hier' => ['2026-08-02 14:00:00', true, 'Un jour, très en deçà du seuil.'];
        yield 'six jours' => ['2026-07-28 14:00:00', true, 'Encore dans la semaine écoulée.'];

        // La borne, et la seule qui se discute : à sept jours pile, le profil a fini d'être nouveau.
        // Une inégalité large le garderait un jour de plus, et le badge changerait d’état en cours
        // de journée selon l’heure de la requête.
        yield 'six jours et vingt-trois heures' => ['2026-07-27 15:00:00', true, 'Une heure avant la borne.'];
        yield 'exactement sept jours' => ['2026-07-27 14:00:00', false, 'La borne est stricte.'];
        yield 'sept jours et une seconde' => ['2026-07-27 13:59:59', false, 'Passé la borne.'];

        yield 'huit jours' => ['2026-07-26 14:00:00', false, 'Bien au-delà du seuil.'];
        yield 'un an' => ['2025-08-03 14:00:00', false, 'Un profil ancien ne redevient jamais nouveau.'];

        // Ne peut pas arriver en base, mais la règle ne doit pas s’effondrer dessus : une horloge
        // décalée d’une seconde entre deux machines suffirait à produire ce cas.
        yield 'daté dans le futur' => ['2026-08-04 14:00:00', true, 'Une date à venir reste récente.'];
    }
}
