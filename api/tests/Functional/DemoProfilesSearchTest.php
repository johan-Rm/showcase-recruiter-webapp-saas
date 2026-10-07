<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Enum\ProfileVisibility;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les profils de démonstration sont-ils trouvables par le chemin que le produit met en avant ?
 *
 * La question n'était pas rhétorique : avant `API-042`, une recherche sur « Développeur logiciel »
 * remontait trois cent cinquante-deux profils importés et **aucun profil de démo**, alors que l'un
 * d'eux était développeur. Ils n'avaient aucun métier reconnu — le filtre cherche un rattachement au
 * dictionnaire, pas un intitulé libre — et aucun écran ne pouvait donc être éprouvé de bout en bout.
 *
 * Ce test garde les deux moitiés de la promesse : les profils de démo entrent dans une recherche par
 * poste, et celui qui n'a pas consenti n'y entre pas malgré un rattachement identique.
 */
final class DemoProfilesSearchTest extends ApiTestCase
{
    public function testADemoProfileIsFoundByItsJob(): void
    {
        $repository = self::getContainer()->get(CandidateRepository::class);

        $names = $this->familyNamesMatching($repository, 'Développeur logiciel');

        self::assertContains('El Amrani', $names, 'le profil de démo historique doit entrer par son métier');
        self::assertContains('Berrada', $names);
    }

    /** Le libellé du dictionnaire, pas celui du poste occupé : les deux diffèrent sur ce profil. */
    public function testTheJobIsResolvedByTheDictionaryNotByTheJobTitle(): void
    {
        $repository = self::getContainer()->get(CandidateRepository::class);

        // « Ingénieur logiciel embarqué » est son intitulé ; « Développeur logiciel » est son métier.
        self::assertContains('Berrada', $this->familyNamesMatching($repository, 'Développeur logiciel'));
        self::assertContains('Ouazzani', $this->familyNamesMatching($repository, 'Comptable'));
        self::assertContains('Benjelloun', $this->familyNamesMatching($repository, 'Commercial'));
    }

    /**
     * Le garde-fou du produit, et c'est pour ça que ce profil est aussi complet que les autres : un
     * profil pauvre resterait absent des résultats pour de mauvaises raisons, et ne prouverait rien.
     */
    public function testTheProfileWithoutConsentStaysOutDespiteTheSameJob(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $hidden = $this->candidateByFamilyName($em, 'Idrissi');
        self::assertSame(ProfileVisibility::HIDDEN, $hidden->visibility);
        self::assertNull($hidden->consentGivenAt, 'le profil témoin ne doit jamais avoir consenti');
        self::assertNotSame([], $hidden->getJobExperiences()->toArray(), 'il porte bien un métier reconnu');

        self::assertNotContains('Idrissi', $this->familyNamesMatching($repository, 'Développeur logiciel'));
    }

    /** @return list<string> */
    private function familyNamesMatching(CandidateRepository $repository, string $job): array
    {
        [$candidates] = $repository->search(new CandidateSearchCriteria(job: $job, perPage: 50));

        return array_map(
            static fn (Candidate $candidate): string => $candidate->getPerson()->familyName,
            $candidates,
        );
    }

    private function candidateByFamilyName(EntityManagerInterface $em, string $familyName): Candidate
    {
        $candidate = $em->createQueryBuilder()
            ->select('c')->from(Candidate::class, 'c')
            ->join('c.person', 'p')
            ->where('p.familyName = :name')->setParameter('name', $familyName)
            ->getQuery()->getSingleResult();

        self::assertInstanceOf(Candidate::class, $candidate);

        return $candidate;
    }
}
