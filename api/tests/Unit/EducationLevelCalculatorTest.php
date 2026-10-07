<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\EducationLevelCalculator;
use App\Enum\EducationLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Niveau d'études dérivé des titres de formation (task 112). Les intitulés du jeu de test sont des
 * intitulés RÉELS du vivier : c'est là que se cachent les pièges (« Bachelor » qui contient « bac »,
 * « bac+2 » qui contient « bac », accents et sigles).
 */
final class EducationLevelCalculatorTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $educations
     */
    #[DataProvider('educationsProvider')]
    public function testFromEducations(array $educations, ?EducationLevel $expected): void
    {
        self::assertSame($expected, EducationLevelCalculator::fromEducations($educations));
    }

    /**
     * @return iterable<string, array{list<array<string, mixed>>, ?EducationLevel}>
     */
    public static function educationsProvider(): iterable
    {
        yield 'aucune formation' => [[], null];

        yield 'intitulé vide → aucun niveau (pas de valeur par défaut)' => [
            [self::edu('')], null,
        ];

        yield 'intitulé non reconnaissable → aucun niveau' => [
            [self::edu('Mamadou Diaw')], null,
        ];

        yield 'le PLUS ÉLEVÉ, pas le plus récent' => [
            [self::edu('Master 2 (M2)'), self::edu('Diplôme universitaire de technologie (DUT)')],
            EducationLevel::MASTER,
        ];

        yield 'doctorat domine' => [
            [self::edu('Master'), self::edu('Doctorat en sciences de gestion')],
            EducationLevel::DOCTORAT,
        ];

        yield 'MBA compte comme master' => [
            [self::edu('Master of Business Administration - MBA')], EducationLevel::MASTER,
        ];

        yield 'ingénieur reconnu malgré l’accent' => [
            [self::edu('Ingénieur')], EducationLevel::INGENIEUR,
        ];

        yield 'mastère spécialisé' => [
            [self::edu('Mastère spécialisé')], EducationLevel::MASTER,
        ];

        yield 'licence professionnelle' => [
            [self::edu('Licence professionnelle')], EducationLevel::LICENCE,
        ];

        // Piège : « Bachelor » contient « bac ». Doit donner licence, pas baccalauréat.
        yield 'Bachelor n’est pas un baccalauréat' => [
            [self::edu('Bachelor of Business Administration - BBA')], EducationLevel::LICENCE,
        ];

        // Piège : « bac+2 » contient « bac ». Doit donner bac+2, pas baccalauréat.
        yield 'bac+2 n’est pas un baccalauréat' => [
            [self::edu('Bac + 2')], EducationLevel::BAC_PLUS_2,
        ];

        yield 'DUT → bac+2' => [
            [self::edu('Diplôme universitaire de technologie (DUT)')], EducationLevel::BAC_PLUS_2,
        ];

        yield 'baccalauréat seul' => [
            [self::edu('Baccalauréat scientifique')], EducationLevel::BAC,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function edu(string $title): array
    {
        return ['title' => $title, 'school' => ['name' => 'École'], 'startDate' => null, 'endDate' => null];
    }
}
