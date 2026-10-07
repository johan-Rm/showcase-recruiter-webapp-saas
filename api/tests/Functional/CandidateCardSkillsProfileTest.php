<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\RevealedAccess;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateLanguage;
use App\Entity\CandidateSectorExperience;
use App\Entity\Person;
use App\Enum\ConsentSource;
use App\Enum\LanguageProficiency;
use App\Enum\ProfileVisibility;
use App\Enum\Sector;
use App\Enum\SyncStatus;
use App\State\CandidateMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Ce que la fiche dit du parcours : les langues parlées, et les secteurs exercés.
 *
 * Les deux existaient en base et restaient invisibles au navigateur, ce qui interdisait d'en faire
 * quoi que ce soit à l'écran alors qu'ils couvrent 88 % et 78 % du vivier.
 *
 * Deux pièges, et ce sont eux que ces tests tiennent : un niveau de langue ABSENT ne veut pas dire
 * débutant — la langue maternelle arrive souvent sans niveau —, et un candidat qui a bifurqué doit
 * rester trouvable sur CHACUN de ses secteurs, pas seulement le plus ancien.
 */
final class CandidateCardSkillsProfileTest extends ApiTestCase
{
    public function testTheCardCarriesEveryLanguageWithItsLevelIncludingWhenAbsent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->persistCandidate($em);

        $candidate->getLanguages()->add(new CandidateLanguage($candidate, 'ar'));
        $candidate->getLanguages()->add(
            new CandidateLanguage($candidate, 'en', LanguageProficiency::PROFESSIONAL_WORKING),
        );
        $em->flush();

        $card = CandidateMapper::toCard($candidate, RevealedAccess::none());

        self::assertSame(
            [
                ['language' => 'ar', 'proficiency' => null],
                ['language' => 'en', 'proficiency' => 'PROFESSIONAL_WORKING'],
            ],
            $card->languages,
            'le niveau absent descend à null, jamais remplacé par un plancher',
        );
    }

    public function testTheCardCarriesEverySectorNotOnlyTheMainOne(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->persistCandidate($em);

        // Une bifurcation : longtemps dans la santé, plus récemment dans la banque.
        $candidate->getSectorExperiences()->add(
            new CandidateSectorExperience($candidate, Sector::SANTE_PHARMA, months: 96),
        );
        $candidate->getSectorExperiences()->add(
            new CandidateSectorExperience($candidate, Sector::BANQUE_FINANCE, months: 18),
        );
        $em->flush();

        $card = CandidateMapper::toCard($candidate, RevealedAccess::none());

        self::assertSame(['SANTE_PHARMA', 'BANQUE_FINANCE'], $card->experienceSectors);
    }

    public function testBothStayEmptyRatherThanAbsentOnAProfileWithoutThem(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->persistCandidate($em);
        $em->flush();

        $card = CandidateMapper::toCard($candidate, RevealedAccess::none());

        // Une liste vide se parcourt ; une clé absente casse le consommateur qui la lit.
        self::assertSame([], $card->languages);
        self::assertSame([], $card->experienceSectors);
    }

    private function persistCandidate(EntityManagerInterface $em): Candidate
    {
        $person = new Person('Test', 'Card-'.uniqid(), contactEmail: uniqid('cd-', true).'@example.test', country: 'FR', city: 'Paris');
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

        $jarvi = new CandidateJarvi($candidate);
        $em->persist($jarvi);
        $candidate->setJarvi($jarvi);

        return $candidate;
    }
}
