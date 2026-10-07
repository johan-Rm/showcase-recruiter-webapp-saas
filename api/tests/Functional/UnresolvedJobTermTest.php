<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\VocabularyConcept;
use App\Entity\VocabularyLabel;
use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Ce que les recruteurs cherchent et que le dictionnaire ignore (task API-024).
 *
 * Le filtre par métier exige une correspondance exacte : un terme hors dictionnaire ramène zéro
 * profil quoi qu'il y ait dans le vivier. On ne saurait jamais lesquels manquent si on ne les gardait
 * pas — le vocabulaire s'enrichirait à l'intuition.
 */
final class UnresolvedJobTermTest extends ApiTestCase
{
    private const string OWNER = 'owner@demo.showcase-recruiter-webapp-saas.example';

    public function testALaunchOnAnUnknownJobLeavesOneTraceAndTheNextOneCountsUp(): void
    {
        $token = $this->loginToken(self::OWNER);
        $term = 'Prompt Engineer '.uniqid();

        $this->launch($token, $term);
        self::assertSame([1, mb_strtolower($term)], $this->trace($term), 'la première recherche laisse une trace');

        // La même, relancée : c'est un compteur, pas une collection de doublons.
        $this->launch($token, $term);
        self::assertSame([2, mb_strtolower($term)], $this->trace($term));

        // La casse ne fait pas deux demandes différentes.
        $this->launch($token, mb_strtoupper($term));
        self::assertSame([3, mb_strtolower($term)], $this->trace($term));
    }

    public function testAKnownJobLeavesNoTraceEvenWhenNobodyExercisesIt(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('kn');

        // Un métier au dictionnaire que PERSONNE n'exerce : la recherche ramènera zéro, et pourtant
        // il n'y a rien à instruire — ce métier est connu, c'est le vivier qui ne l'a pas.
        $em->persist(new VocabularyConcept(VocabularyType::METIER, 'Cordiste '.$tag));
        $em->flush();

        $this->launch($this->loginToken(self::OWNER), 'Cordiste '.$tag);

        self::assertNull($this->trace('Cordiste '.$tag), 'un métier connu ne s’instruit pas');
    }

    public function testAVariantOfAKnownJobLeavesNoTraceEither(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tag = uniqid('vr');

        $concept = new VocabularyConcept(VocabularyType::METIER, 'Développeur '.$tag);
        $em->persist($concept);
        $em->persist(new VocabularyLabel($concept, 'software engineer '.$tag, 'en'));
        $em->flush();

        // Le filtre accepte les libellés vivants, pas seulement le canonique : la trace doit suivre
        // exactement la même résolution, sinon elle réclamerait d'ajouter ce qui existe déjà.
        $this->launch($this->loginToken(self::OWNER), 'software engineer '.$tag);

        self::assertNull($this->trace('software engineer '.$tag));
    }

    public function testASearchWithoutAJobCriterionLeavesNothing(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'q', 'value' => 'kubernetes', 'group' => 'metier']],
        ]);
        self::assertResponseIsSuccessful();

        self::assertSame(0, $this->connection()->fetchOne(
            'SELECT count(*) FROM unresolved_job_term WHERE term = :t',
            ['t' => 'kubernetes'],
        ));
    }

    public function testTheCommandListsTheMostSoughtFirst(): void
    {
        $token = $this->loginToken(self::OWNER);
        $rare = 'Sourcier '.uniqid();
        $common = 'Cosmonaute '.uniqid();

        $this->launch($token, $rare);
        $this->launch($token, $common);
        $this->launch($token, $common);

        $tester = new CommandTester(
            (new Application(self::$kernel))->find('app:vocabulary:missing'),
        );
        $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString($common, $display);
        self::assertLessThan(
            mb_strpos($display, $rare),
            mb_strpos($display, $common),
            'le plus cherché passe devant',
        );
    }

    /** Lance une recherche sur cet intitulé — côté produit, composer des critères CRÉE la recherche. */
    private function launch(string $token, string $job): void
    {
        $this->request('POST', '/v1/searches', $token, [
            'criteria' => [['facet' => 'job', 'value' => $job, 'group' => 'metier']],
        ]);
        self::assertResponseIsSuccessful();
    }

    /** @return array{0: int, 1: string}|null le compteur et le terme normalisé, ou `null` sans trace */
    private function trace(string $term): ?array
    {
        /** @var array{occurrences: int|string, term: string}|false $row */
        $row = $this->connection()->fetchAssociative(
            'SELECT occurrences, term FROM unresolved_job_term WHERE term = :t',
            ['t' => mb_strtolower($term)],
        );

        return false === $row ? null : [(int) $row['occurrences'], $row['term']];
    }

    private function connection(): Connection
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
