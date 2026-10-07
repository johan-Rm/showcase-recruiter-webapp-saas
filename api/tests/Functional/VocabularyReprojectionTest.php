<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Vocabulary\VocabularyImporter;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

/**
 * Ce qui rend une correction du dictionnaire effective — task API-035.
 *
 * Deux mécanismes, et ils ne valent que l'un par l'autre. Le fichier versionné doit pouvoir RETIRER
 * un libellé, sans quoi un rattachement erroné est définitif ; et le dictionnaire doit pouvoir se
 * REJOUER sur le vivier déjà chargé, sans quoi le retrait ne déplace personne et reste invisible.
 *
 * Le retrait est éprouvé sur la RECONNAISSANCE D'INTITULÉ, pas sur la colonne `deleted_at`. C'est ce
 * consommateur qui départageait les deux mécanismes possibles au cadrage : la recherche et
 * l'auto-complétion excluent aussi les libellés dépréciés, la reconnaissance ne regarde que la
 * suppression douce. Vérifier la colonne ne prouverait rien du choix qui a été fait.
 *
 * ⚠️ CHAQUE REPROJECTION REDÉMARRE LE NOYAU. Le catalogue des postes est chargé une fois puis gardé
 * en mémoire ({@see \App\Domain\Vocabulary\JobConceptMap}) : une commande de console, qui vit dans
 * son propre processus, le voit donc toujours frais. Sans ce redémarrage, le test éprouverait un
 * cache que la production n'a pas, et verrait le dictionnaire d'avant l'import.
 */
final class VocabularyReprojectionTest extends ApiTestCase
{
    /** Intitulé du profil de test — assez singulier pour n'attraper aucun libellé du stock. */
    private const string TITLE = 'zzztest praticien du vide';

    private const string CONCEPT = 'Zzztest';

    private const string OTHER_CONCEPT = 'Zzztest bis';

    public function testALabelDroppedFromTheFileStopsAttachingProfiles(): void
    {
        $candidate = $this->seedCandidate();

        $this->import([[self::CONCEPT, [self::TITLE]]]);
        $this->reproject();
        self::assertSame([self::CONCEPT], $this->exercisedJobs($candidate));

        $this->import([[self::CONCEPT, []]]);
        $this->reproject();
        self::assertSame([], $this->exercisedJobs($candidate), 'le libellé retiré ne rattache plus');
    }

    public function testALabelGivenBackToTheFileComesBackOnItsOwnRow(): void
    {
        $this->import([[self::CONCEPT, [self::TITLE]]]);
        $uuid = $this->labelUuid();

        $this->import([[self::CONCEPT, []]]);
        $this->import([[self::CONCEPT, [self::TITLE]]]);

        self::assertSame($uuid, $this->labelUuid(), 'le libellé rendu reprend sa ligne');
        self::assertSame(1, $this->labelRows(), 'aucune ligne en double');
    }

    /** Un concept que le fichier ne décrit pas garde ses libellés : le silence ne vaut pas retrait. */
    public function testAConceptAbsentFromTheFileKeepsItsLabels(): void
    {
        $candidate = $this->seedCandidate();

        $this->import([[self::CONCEPT, [self::TITLE]]]);
        $this->import([]);   // taxonomie tronquée, pas un retrait
        $this->reproject();

        self::assertSame([self::CONCEPT], $this->exercisedJobs($candidate));
    }

    /**
     * Le cas qui justifie la commande : charger le dictionnaire corrigé ne suffit pas.
     *
     * C'est l'assertion du milieu qui porte la tâche — sans elle, on ne verrait pas que le
     * rattachement est figé depuis l'ingestion et qu'un import seul le laisse tel quel.
     */
    public function testTheDictionaryOnlyMovesProfilesOnceReprojected(): void
    {
        $candidate = $this->seedCandidate();

        $this->import([[self::CONCEPT, [self::TITLE]]]);
        $this->reproject();
        self::assertSame([self::CONCEPT], $this->exercisedJobs($candidate));

        // Le libellé passe d'un concept à l'autre. La réplica du profil, elle, n'est pas retouchée.
        $this->import([[self::CONCEPT, []], [self::OTHER_CONCEPT, [self::TITLE]]]);
        self::assertSame(
            [self::CONCEPT],
            $this->exercisedJobs($candidate),
            'l’import seul ne déplace personne',
        );

        $tester = $this->reproject();
        self::assertSame([self::OTHER_CONCEPT], $this->exercisedJobs($candidate));
        self::assertStringContainsString('ligne(s) de projection', $tester->getDisplay());
    }

    /** `--dry-run` calcule et compte, il n'écrit pas. */
    public function testReprojectionDryRunWritesNothing(): void
    {
        $candidate = $this->seedCandidate();

        $this->import([[self::CONCEPT, [self::TITLE]]]);
        $this->reproject();

        $this->import([[self::CONCEPT, []], [self::OTHER_CONCEPT, [self::TITLE]]]);
        $this->reproject(['--dry-run' => true]);

        self::assertSame([self::CONCEPT], $this->exercisedJobs($candidate), 'rien n’a été écrit');
    }

    /**
     * Charge des concepts de test, puis vide l'unité de travail : la reconnaissance d'intitulé lit
     * en SQL natif et ne verrait pas des entités restées en mémoire.
     *
     * @param list<array{0: string, 1: list<string>}> $concepts libellé canonique → ses libellés
     */
    private function import(array $concepts): void
    {
        self::getContainer()->get(VocabularyImporter::class)->import([
            'postes' => [
                'concepts' => array_map(
                    static fn (array $concept): array => [
                        'id' => 'zzztest-'.mb_strtolower(str_replace(' ', '-', $concept[0])),
                        'label_canonique' => $concept[0],
                        'frequence_totale' => \count($concept[1]),
                        'synonymes' => array_map(
                            static fn (string $label): array => ['terme' => $label, 'frequence' => 1, 'langue' => 'fr'],
                            $concept[1],
                        ),
                    ],
                    $concepts,
                ),
            ],
        ]);
        $this->em()->clear();
    }

    /** @param array<string, mixed> $options */
    private function reproject(array $options = []): CommandTester
    {
        self::ensureKernelShutdown();
        $tester = new CommandTester((new Application(self::bootKernel()))->find('app:vocabulary:reproject'));
        $tester->execute($options);
        $tester->assertCommandIsSuccessful();

        return $tester;
    }

    /**
     * Métiers exercés effectivement posés sur le profil — l'état observable, pas un calcul refait.
     *
     * @return list<string>
     */
    private function exercisedJobs(int $candidateId): array
    {
        return array_map(strval(...), $this->em()->getConnection()->fetchFirstColumn(
            'SELECT vc.pref_label FROM candidate_job_experience cje'
            .' JOIN vocabulary_concept vc ON vc.id = cje.concept_id'
            .' WHERE cje.candidate_id = :id ORDER BY vc.pref_label',
            ['id' => $candidateId],
        ));
    }

    private function labelUuid(): string
    {
        return (string) $this->em()->getConnection()->fetchOne(
            'SELECT uuid FROM vocabulary_label WHERE label = :label AND deleted_at IS NULL',
            ['label' => self::TITLE],
        );
    }

    private function labelRows(): int
    {
        return (int) $this->em()->getConnection()->fetchOne(
            'SELECT count(*) FROM vocabulary_label WHERE label = :label',
            ['label' => self::TITLE],
        );
    }

    /** Toujours relu depuis le conteneur courant : le noyau redémarre entre deux reprojections. */
    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function seedCandidate(): int
    {
        $em = $this->em();
        $person = new Person('Test', 'Reproject-'.uniqid(), contactEmail: uniqid('rp-', true).'@example.test', country: 'FR', city: 'Paris');
        $em->persist($person);

        $candidate = new Candidate(
            $person,
            jarviProfileId: Uuid::v7(),
            visibility: ProfileVisibility::VISIBLE,
            syncStatus: SyncStatus::SYNCED,
            syncedAt: new DateTimeImmutable(),
        );
        $candidate->giveConsent(ConsentSource::ONBOARDING, new DateTimeImmutable());
        $em->persist($candidate);

        $jarvi = new CandidateJarvi($candidate, workExperiences: [[
            'title' => self::TITLE,
            'companyName' => 'Zzztest SA',
            'startDate' => '2020-01-01',
            'endDate' => null,
        ]]);
        $em->persist($jarvi);
        $candidate->setJarvi($jarvi);
        $em->flush();

        return (int) $candidate->getId();
    }
}
