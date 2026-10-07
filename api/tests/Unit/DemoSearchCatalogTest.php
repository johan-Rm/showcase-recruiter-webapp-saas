<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Search\Demo\DemoSearchCatalog;
use App\Domain\Search\Demo\DemoSearchDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une entrée du catalogue de démonstration a le droit d'être — task API-029.
 *
 * Ces règles ne se voient pas en lisant une carte à l'écran : il faut les vérifier sur les soixante
 * entrées à la fois. Une seule qui déroge, et c'est la grille entière qui perd sa valeur de
 * démonstration — une carte vide, un titre confondu avec un essai, ou une recherche qui ramène le
 * quart du vivier parce qu'elle demande « anglais ».
 */
final class DemoSearchCatalogTest extends TestCase
{
    /**
     * Les langues, jamais en mot-clé.
     *
     * « Anglais » est portée par un profil sur quatre : en faire un mot-clé transformerait une
     * recherche ciblée en recherche large, sans que la carte le dise.
     */
    private const array LANGUAGES = [
        'anglais', 'english', 'français', 'french', 'arabe', 'arabic', 'espagnol', 'spanish',
        'allemand', 'german', 'italien', 'italian', 'néerlandais', 'portugais', 'chinois', 'langues',
    ];

    /** Les groupes de facettes qui RESSERRENT le jeu, par opposition aux mots-clés qui l'élargissent. */
    private const array NARROWING = ['experience', 'etudes', 'localisation'];

    public function testTheGridIsLargeEnoughToJudgeALayout(): void
    {
        self::assertGreaterThanOrEqual(50, \count($this->definitions()));
    }

    /** Aucune carte vide : une recherche sans critère ne ramène rien, et ne montre donc rien. */
    public function testEveryDefinitionCarriesAtLeastTwoCriteria(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            self::assertGreaterThanOrEqual(
                2,
                \count($definition->criteria),
                \sprintf('Entrée %d : une recherche du catalogue porte au moins deux critères.', $index),
            );
        }
    }

    /** Chaque recherche part d'un métier : c'est lui qui la nomme et qui borne son jeu. */
    public function testEveryDefinitionStartsFromAJob(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            self::assertSame(
                'job',
                $definition->criteria[0]['facet'] ?? null,
                \sprintf('Entrée %d : le métier ouvre les critères.', $index),
            );
        }
    }

    public function testNoKeywordIsALanguage(): void
    {
        foreach ($this->definitions() as $definition) {
            foreach ($definition->criteria as $criterion) {
                if ('q' !== $criterion['facet']) {
                    continue;
                }
                self::assertNotContains(
                    mb_strtolower($criterion['value']),
                    self::LANGUAGES,
                    'Une langue en mot-clé ramènerait un quart du vivier.',
                );
            }
        }
    }

    /**
     * Un critère qui RESSERRE — seuil d'expérience, niveau d'études, pays — n'est jamais posé plus
     * de deux fois sur la même recherche (task API-033).
     *
     * C'est le cumul qui vide une carte, pas la présence d'un filtre. Le seuil d'expérience écarte
     * les profils dont les années n'ont pas pu être calculées, le niveau d'études les 23 % dont le
     * diplôme n'est pas reconnu : les empiler tous les trois ne laisse presque personne, même sur un
     * métier fourni. La règle précédente comptait les recherches restrictives — elle mesurait à côté
     * du risque, et interdisait le cadrage voulu.
     *
     * Les mots-clés, eux, ne comptent pas : ils se relient en OU, donc ils élargissent.
     */
    public function testNoSearchStacksMoreThanTwoNarrowingCriteria(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            $narrowing = array_filter(
                $definition->criteria,
                static fn (array $criterion): bool => \in_array($criterion['group'], self::NARROWING, true),
            );

            self::assertLessThanOrEqual(
                2,
                \count($narrowing),
                \sprintf('Entrée %d : trois critères restrictifs ne laissent presque personne.', $index),
            );
        }
    }

    /**
     * Deux entrées aux mêmes critères seraient la même recherche : le seeder n'en créerait qu'une,
     * et la grille perdrait une carte sans que rien ne le signale.
     */
    public function testNoTwoDefinitionsShareTheSameCriteria(): void
    {
        $seen = [];
        foreach ($this->definitions() as $definition) {
            $signature = json_encode($definition->criteria, \JSON_THROW_ON_ERROR);
            self::assertNotContains($signature, $seen, 'Deux entrées du catalogue sont identiques.');
            $seen[] = $signature;
        }
    }

    /** Tous les états de carte doivent être représentés, sans quoi la grille n'en montre qu'un. */
    public function testEveryCardStateIsRepresented(): void
    {
        $definitions = $this->definitions();

        self::assertNotEmpty(array_filter($definitions, static fn (DemoSearchDefinition $d): bool => $d->closed));
        self::assertNotEmpty(array_filter($definitions, static fn (DemoSearchDefinition $d): bool => $d->shared));
        self::assertNotEmpty(array_filter($definitions, static fn (DemoSearchDefinition $d): bool => $d->byColleague));
        self::assertNotEmpty(array_filter($definitions, static fn (DemoSearchDefinition $d): bool => '' !== $d->name));
    }

    /** @return list<DemoSearchDefinition> */
    private function definitions(): array
    {
        return (new DemoSearchCatalog())->definitions();
    }
}
