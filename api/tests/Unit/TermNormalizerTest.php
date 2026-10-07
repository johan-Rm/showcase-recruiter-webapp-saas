<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\TermNormalizer;
use PHPUnit\Framework\TestCase;

final class TermNormalizerTest extends TestCase
{
    private TermNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TermNormalizer();
    }

    public function testNormalizesCaseQuotesAndSpacing(): void
    {
        self::assertSame("ingénieur d'études", $this->normalizer->normalize('  Ingénieur   d’Études  '));
    }

    /** La ponctuation de bord porte du sens sur les compétences : `normalize()` n'y touche pas. */
    public function testNormalizeKeepsEdgePunctuation(): void
    {
        self::assertSame('.net framework', $this->normalizer->normalize('.NET Framework'));
    }

    public function testCanonicalizeTitleCutsAtTheFirstNoiseSeparator(): void
    {
        self::assertSame('product owner', $this->normalizer->canonicalizeTitle('Product Owner / AMOA | Renewables'));
        self::assertSame('développeur', $this->normalizer->canonicalizeTitle('Développeur - Équipe paiement'));
        self::assertSame('data analyst', $this->normalizer->canonicalizeTitle('Data Analyst chez showcase-recruiter-webapp-saas'));
    }

    public function testCanonicalizeTitleTrimsEdgePunctuation(): void
    {
        self::assertSame("ingénieur d'études", $this->normalizer->canonicalizeTitle('  Ingénieur d’Études.  '));
    }

    /**
     * Régression : le rognage se faisait avec `trim()` et une liste de caractères, donc **par octet**.
     * Les octets isolés de « · » et « — » amputaient les caractères multi-octets voisins, produisant
     * de l'UTF-8 invalide — ce qui faisait échouer silencieusement l'export de `app:vocabulary:harvest`.
     */
    public function testCanonicalizeTitleKeepsMultibyteCharactersIntact(): void
    {
        $term = $this->normalizer->canonicalizeTitle('مدير ط');

        self::assertSame('مدير ط', $term);
        self::assertNotFalse(json_encode($term), 'le terme rogné doit rester encodable en JSON');
    }
}
