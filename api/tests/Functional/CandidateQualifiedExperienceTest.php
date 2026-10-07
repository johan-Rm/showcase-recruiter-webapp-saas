<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Candidate\RevealedAccess;
use App\Entity\CandidateJobExperience;
use App\Entity\CandidateLanguage;
use App\Entity\CandidateSectorExperience;
use App\Entity\VocabularyConcept;
use App\Enum\LanguageProficiency;
use App\Enum\Sector;
use App\Enum\VocabularyType;
use App\Repository\CandidateRepository;
use App\State\CandidateMapper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Expérience qualifiée sur la fiche candidat (task 122).
 *
 * L'ingestion produit 4 163 lignes d'expérience métier et 3 022 de secteur : elles étaient
 * **filtrables mais illisibles**. Un recruteur trouvait un profil par son métier, ouvrait sa fiche,
 * et n'y voyait pas ce métier.
 */
final class CandidateQualifiedExperienceTest extends ApiTestCase
{
    public function testExposesJobsSectorsAndLanguagesEvenWhenMasked(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = self::getContainer()->get(CandidateRepository::class)->findActive()[0];

        // LE PROFIL EST VIDÉ DE SON EXPÉRIENCE QUALIFIÉE avant d'en recevoir une maîtrisée : depuis
        // API-042 les profils de démo en portent une, et ce test comptait sur leur pauvreté — il
        // butait sur la contrainte d'unicité du secteur, et lisait un métier qui n'était pas le sien.
        foreach ($candidate->getJobExperiences() as $existing) {
            $em->remove($existing);
        }
        foreach ($candidate->getSectorExperiences() as $existing) {
            $em->remove($existing);
        }
        $em->flush();
        $candidate->getJobExperiences()->clear();
        $candidate->getSectorExperiences()->clear();

        $long = new VocabularyConcept(VocabularyType::METIER, 'Métier long-'.uniqid());
        $short = new VocabularyConcept(VocabularyType::METIER, 'Métier court-'.uniqid());
        $em->persist($long);
        $em->persist($short);

        // Le court est ajouté EN PREMIER : le tri par durée doit malgré tout remonter le long.
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $short, months: 6, positions: 1, exactPositions: 0));
        $candidate->getJobExperiences()->add(new CandidateJobExperience($candidate, $long, months: 48, positions: 3, exactPositions: 2));
        $candidate->getSectorExperiences()->add(new CandidateSectorExperience($candidate, Sector::INFORMATIQUE, 36));
        $candidate->getLanguages()->add(new CandidateLanguage($candidate, 'fr', LanguageProficiency::NATIVE_OR_BILINGUAL));
        $candidate->getLanguages()->add(new CandidateLanguage($candidate, 'de'));
        $em->flush();

        // MASQUÉ : ces dimensions décrivent sans nommer, elles doivent sortir quand même — c'est là
        // qu'elles servent, pour décider d'un dévoilement.
        $output = CandidateMapper::toDetail($candidate, RevealedAccess::none());

        self::assertFalse($output->revealedEmail);
        self::assertSame($long->prefLabel, $output->jobExperiences[0]['job'], 'tri par durée décroissante');
        self::assertSame(48, $output->jobExperiences[0]['months']);
        self::assertSame(3, $output->jobExperiences[0]['positions']);
        self::assertSame(2, $output->jobExperiences[0]['exactPositions'], 'la part reconnue à l’identique accompagne la durée');
        self::assertSame($short->prefLabel, $output->jobExperiences[1]['job']);

        self::assertSame([['sector' => 'INFORMATIQUE', 'months' => 36]], $output->sectorExperiences);

        $levels = array_column($output->spokenLanguages, 'proficiency', 'language');
        self::assertSame('NATIVE_OR_BILINGUAL', $levels['fr']);
        self::assertNull($levels['de'], 'un niveau absent n’est jamais supposé');
    }
}
