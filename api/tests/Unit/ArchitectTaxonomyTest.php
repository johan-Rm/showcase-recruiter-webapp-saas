<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\JobConceptEntry;
use App\Domain\Vocabulary\ResolvedJob;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Tests\Support\InMemoryJobConceptCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Les architectes du dictionnaire, éprouvés sur le FICHIER LIVRÉ — task API-036.
 *
 * Ce test ne vérifie pas la règle de reconnaissance : `TitleConceptResolverTest` s'en charge. Il
 * vérifie la DONNÉE, et c'est un objet de test inhabituel qu'il faut justifier.
 *
 * Le défaut corrigé n'était pas dans le code : le résolveur faisait exactement ce qu'on lui
 * demandait. Il était dans `taxonomy.json`, où le concept informatique portait la variante nue
 * « architecte » — si bien qu'un architecte d'intérieur, un paysagiste et un architecte du bâtiment
 * étaient servis à qui cherchait un architecte logiciel. Un fichier de données peut donc casser le
 * produit sans qu'aucun test de code ne bronche, et c'est ce trou que celui-ci ferme.
 *
 * IL LIT LE VRAI FICHIER, pas un échantillon : c'est la seule façon qu'il échoue si quelqu'un
 * remet « architecte » sur le concept informatique, ou retire un des trois concepts créés. Il ne
 * charge que les concepts d'architecture — les huit cent soixante autres ne le regardent pas.
 */
final class ArchitectTaxonomyTest extends TestCase
{
    private const string TAXONOMY = __DIR__.'/../../data/vocabulary/taxonomy.json';

    /**
     * Ce que chaque intitulé du vivier doit désormais désigner.
     *
     * Les six premiers sont des intitulés RÉELS, relevés dans le vivier : ce sont eux qui ont motivé
     * la tâche. Les deux derniers protègent l'élargissement, qui ne doit pas avoir été perdu au
     * passage.
     *
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function titles(): iterable
    {
        yield 'un architecte d’intérieur n’est pas informaticien' => [
            "architecte d'intérieur",
            "Architecte d'intérieur",
        ];
        yield 'même sans accent, tel qu’il est écrit dans deux profils' => [
            "architecte d'interieur",
            "Architecte d'intérieur",
        ];
        yield 'un stagiaire garde le métier, pas le statut' => [
            "stagiaire architecte d'intérieur",
            "Architecte d'intérieur",
        ];
        yield 'l’anglais rejoint le même concept' => ['interior architect', "Architecte d'intérieur"];
        yield 'le paysagiste a le sien' => ['architecte paysagiste', 'Architecte paysagiste'];
        yield 'le mot nu ne dit pas l’informatique' => ['architecte', 'Architecte'];
        yield 'un architecte logiciel reste informaticien' => [
            'architecte logiciel',
            'Architecte (IT/logiciel)',
        ];
        yield 'et l’anglais aussi' => ['solution architect', 'Architecte (IT/logiciel)'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('titles')]
    public function testTitleResolvesToItsConcept(string $title, string $expected): void
    {
        $resolved = $this->resolver()->resolve($title);

        self::assertInstanceOf(ResolvedJob::class, $resolved, \sprintf('« %s » ne résout vers rien', $title));
        self::assertSame(
            $expected,
            $this->labels()[$resolved->conceptId] ?? '?',
            \sprintf('« %s » doit désigner « %s »', $title, $expected),
        );
    }

    /** Le concept informatique ne revendique plus le mot nu — la cause du défaut, tenue à part. */
    public function testTheItConceptNoLongerClaimsTheBareWord(): void
    {
        $terms = [];
        foreach (self::architectConcepts() as $concept) {
            if ('Architecte (IT/logiciel)' === $concept['label_canonique']) {
                $terms = array_column($concept['synonymes'], 'terme');
            }
        }

        self::assertNotEmpty($terms, 'le concept informatique doit exister');
        self::assertNotContains('architecte', $terms);
        self::assertContains('architecte logiciel', $terms);
    }

    /** Un résolveur alimenté par les seuls concepts d'architecture du fichier versionné. */
    private function resolver(): TitleConceptResolver
    {
        $entries = [];
        foreach (self::architectConcepts() as $index => $concept) {
            foreach ($concept['synonymes'] as $synonyme) {
                $entries[] = new JobConceptEntry($synonyme['terme'], $index, $synonyme['frequence'], false);
            }
        }

        // « Stage / Internship » n'est pas un concept d'architecture, mais sans lui la règle « le
        // métier bat le statut » n'a rien à départager : le cas du stagiaire ne prouverait rien.
        $entries[] = new JobConceptEntry('stagiaire', -1, 300, true);

        return new TitleConceptResolver(new InMemoryJobConceptCatalog($entries));
    }

    /** @return array<int, string> identifiant de test → libellé canonique */
    private function labels(): array
    {
        return array_map(
            static fn (array $concept): string => $concept['label_canonique'],
            self::architectConcepts(),
        );
    }

    /**
     * Les concepts d'architecture du fichier versionné, indexés par leur rang.
     *
     * @return array<int, array{label_canonique: string, synonymes: list<array{terme: string, frequence: int}>}>
     */
    private static function architectConcepts(): array
    {
        $taxonomy = json_decode((string) file_get_contents(self::TAXONOMY), true, flags: \JSON_THROW_ON_ERROR);

        $concepts = array_values(array_filter(
            $taxonomy['postes']['concepts'],
            static fn (array $concept): bool => str_contains($concept['label_canonique'], 'Architecte'),
        ));

        self::assertNotEmpty($concepts, 'le fichier doit porter des concepts d’architecture');

        return $concepts;
    }
}
