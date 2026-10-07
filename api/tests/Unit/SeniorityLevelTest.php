<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Enum\SeniorityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Séniorité dérivée de l'ancienneté (task 095) : bornes des seuils junior < 2, confirmé 2–7,
 * senior > 7.
 */
final class SeniorityLevelTest extends TestCase
{
    #[DataProvider('yearsProvider')]
    public function testFromYears(int $years, SeniorityLevel $expected): void
    {
        self::assertSame($expected, SeniorityLevel::fromYears($years));
    }

    /**
     * @return iterable<string, array{int, SeniorityLevel}>
     */
    public static function yearsProvider(): iterable
    {
        yield '0 an → junior' => [0, SeniorityLevel::JUNIOR];
        yield '1 an → junior' => [1, SeniorityLevel::JUNIOR];
        yield '2 ans → confirmé (borne basse)' => [2, SeniorityLevel::CONFIRME];
        yield '7 ans → confirmé (borne haute)' => [7, SeniorityLevel::CONFIRME];
        yield '8 ans → senior' => [8, SeniorityLevel::SENIOR];
        yield '20 ans → senior' => [20, SeniorityLevel::SENIOR];
    }
}
