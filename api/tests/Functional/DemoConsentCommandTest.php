<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Command\ConsentDemoVivierCommand;
use App\Domain\Candidate\DemoConsentGranter;
use App\Entity\Candidate;
use App\Enum\ConsentSource;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * L'ouverture d'un vivier de démonstration — task API-032.
 *
 * Ce qui doit être prouvé n'est pas qu'une commande écrit des dates, mais qu'elle **ne touche jamais
 * un consentement donné par une personne** : ni en l'ouvrant, ni en le refermant. C'est cette
 * garantie, et elle seule, qui rend le geste acceptable sur un vivier de profils réels.
 */
final class DemoConsentCommandTest extends ApiTestCase
{
    /** Les fixtures posent deux profils consentis (ONBOARDING) et un profil masqué sans consentement. */
    public function testItOpensOnlyTheProfilesThatWereClosed(): void
    {
        $genuineBefore = $this->countBySource(ConsentSource::ONBOARDING);
        self::assertGreaterThan(0, $genuineBefore, 'fixtures trop maigres pour ce test');
        $closedBefore = $this->countWithoutConsent();
        self::assertGreaterThan(0, $closedBefore, 'fixtures sans profil fermé : le test ne prouverait rien');

        $tester = $this->consent();

        self::assertStringContainsString(\sprintf('%d profil(s) ouvert(s)', $closedBefore), $tester->getDisplay());
        self::assertSame(0, $this->countWithoutConsent(), 'Il reste un profil fermé.');
        self::assertSame($closedBefore, $this->countBySource(ConsentSource::DEMONSTRATION));
        self::assertSame($genuineBefore, $this->countBySource(ConsentSource::ONBOARDING), 'Un consentement réel a été réécrit.');
    }

    /** Le geste humain est intouchable : sa date comme sa source survivent à l'ouverture. */
    public function testAGenuineConsentKeepsItsDateAndSource(): void
    {
        $candidate = $this->aConsentedCandidate();
        $date = $candidate->consentGivenAt;
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $this->consent();

        $this->em()->clear();
        $reloaded = $this->em()->getRepository(Candidate::class)->find($candidate->getId());
        self::assertInstanceOf(Candidate::class, $reloaded);
        self::assertSame(ConsentSource::ONBOARDING, $reloaded->consentSource);
        self::assertSame($date->format(\DATE_ATOM), $reloaded->consentGivenAt?->format(\DATE_ATOM));
    }

    /** Rejouée, elle n'a plus rien à ouvrir — et ne réécrit pas ce qu'elle a posé la première fois. */
    public function testRunningItTwiceOpensNothingMore(): void
    {
        $this->consent();
        $opened = $this->countBySource(ConsentSource::DEMONSTRATION);

        $tester = $this->consent();

        self::assertStringContainsString('0 profil(s) ouvert(s)', $tester->getDisplay());
        self::assertSame($opened, $this->countBySource(ConsentSource::DEMONSTRATION));
    }

    /**
     * La révocation referme EXACTEMENT ce que l'ouverture a ouvert.
     *
     * C'est la promesse qui autorise le geste : sans elle, ouvrir un vivier de profils réels serait
     * sans retour, faute de savoir quoi refermer.
     */
    public function testRevokeClosesOnlyWhatWasOpenedForDemonstration(): void
    {
        $genuine = $this->countBySource(ConsentSource::ONBOARDING);
        $this->consent();

        $tester = $this->consent(['--revoke' => true]);

        self::assertStringContainsString('refermé(s)', $tester->getDisplay());
        self::assertSame(0, $this->countBySource(ConsentSource::DEMONSTRATION));
        self::assertSame($genuine, $this->countBySource(ConsentSource::ONBOARDING), 'Un consentement réel a été retiré.');
    }

    /** En production, elle refuse — comme toute commande d'amorçage de ce dépôt. */
    public function testItRefusesToRunInProduction(): void
    {
        // La commande est construite à la main avec l'environnement de production : c'est le seul
        // moyen d'éprouver le refus sans faire tourner le noyau dans cet environnement.
        $tester = new CommandTester(new ConsentDemoVivierCommand(
            self::getContainer()->get(DemoConsentGranter::class),
            'prod',
        ));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Refus', $tester->getDisplay());
        self::assertSame(0, $this->countBySource(ConsentSource::DEMONSTRATION));
    }

    /** @param array<string, bool> $options */
    private function consent(array $options = []): CommandTester
    {
        $application = new Application(self::$kernel ?? self::bootKernel());
        $tester = new CommandTester($application->find('app:candidates:consent-demo'));
        $tester->execute($options);
        $tester->assertCommandIsSuccessful();

        return $tester;
    }

    private function aConsentedCandidate(): Candidate
    {
        $candidate = $this->em()->getRepository(Candidate::class)->findOneBy(['consentSource' => ConsentSource::ONBOARDING]);
        self::assertInstanceOf(Candidate::class, $candidate);

        return $candidate;
    }

    private function countBySource(ConsentSource $source): int
    {
        return \count($this->em()->getRepository(Candidate::class)->findBy(['consentSource' => $source]));
    }

    private function countWithoutConsent(): int
    {
        return \count($this->em()->getRepository(Candidate::class)->findBy(['consentGivenAt' => null]));
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
