<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\ProfileView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Trace des visites de profil (tâche API-072) : ouvrir la fiche d'un candidat laisse une trace, une
 * seule par recruteur, par candidat et par jour. Visiter n'est pas dévoiler — aucun crédit n'entre
 * en jeu ici.
 */
final class ProfileViewTest extends ApiTestCase
{
    private const string OWNER_EMAIL = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string ATLAS_OWNER_EMAIL = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string ATLAS_MEMBER_EMAIL = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';
    private const string ADMIN_EMAIL = 'admin@demo.showcase-recruiter-webapp-saas.example';

    public function testOpeningACandidateRecordsAVisit(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $candidateId = $this->aVisibleCandidateId($token);
        $before = $this->countVisits($candidateId);

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);

        self::assertResponseIsSuccessful();
        self::assertSame($before + 1, $this->countVisits($candidateId));
    }

    public function testReopeningTheSameDayRecordsNothingMore(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $candidateId = $this->aVisibleCandidateId($token);

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();
        $afterFirst = $this->countVisits($candidateId);

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();
        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();

        self::assertSame($afterFirst, $this->countVisits($candidateId));
    }

    /** La maille est le recruteur, pas l'entreprise : deux collègues laissent deux traces. */
    public function testEachRecruiterOfTheSameOrganizationRecordsOwnVisit(): void
    {
        $ownerToken = $this->loginToken(self::ATLAS_OWNER_EMAIL);
        $candidateId = $this->aVisibleCandidateId($ownerToken);

        $this->request('GET', "/v1/candidates/{$candidateId}", $ownerToken);
        self::assertResponseIsSuccessful();
        $afterOwner = $this->countVisits($candidateId);

        $this->request('GET', "/v1/candidates/{$candidateId}", $this->loginToken(self::ATLAS_MEMBER_EMAIL));
        self::assertResponseIsSuccessful();

        self::assertSame($afterOwner + 1, $this->countVisits($candidateId));
    }

    /** Le jour civil borne la déduplication : une visite de la veille n'empêche pas celle du jour. */
    public function testYesterdaysVisitDoesNotBlockTodays(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $candidateId = $this->aVisibleCandidateId($token);

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();
        $afterFirst = $this->countVisits($candidateId);
        $this->backdateVisits($candidateId);

        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseIsSuccessful();

        self::assertSame($afterFirst + 1, $this->countVisits($candidateId));
    }

    /** Sans recruteur rattaché, la fiche est refusée — et rien ne s'écrit. */
    public function testAnAdminRecordsNothing(): void
    {
        $candidateId = $this->aVisibleCandidateId($this->loginToken(self::OWNER_EMAIL));
        $before = $this->countVisits($candidateId);

        $this->request('GET', "/v1/candidates/{$candidateId}", $this->loginToken(self::ADMIN_EMAIL));

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->countVisits($candidateId));
    }

    private function aVisibleCandidateId(string $token): string
    {
        $this->request('GET', '/v1/search/candidates', $token);
        self::assertResponseIsSuccessful();

        $items = $this->jsonResponse()['items'];
        self::assertNotEmpty($items, 'La recherche doit ramener au moins un candidat exposable.');

        return $items[0]['id'];
    }

    private function countVisits(string $candidateUuid): int
    {
        $count = $this->entityManager()
            ->createQuery(
                'SELECT COUNT(v.id) FROM App\Entity\ProfileView v JOIN v.candidate c WHERE c.uuid = :uuid'
            )
            ->setParameter('uuid', $candidateUuid)
            ->getSingleScalarResult();

        return (int) $count;
    }

    /** Recule d'un jour les visites du candidat : le lendemain se provoque, il ne s'attend pas. */
    private function backdateVisits(string $candidateUuid): void
    {
        $em = $this->entityManager();
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['uuid' => $candidateUuid]);
        self::assertInstanceOf(Candidate::class, $candidate);

        $em->createQuery(
            'UPDATE '.ProfileView::class.' v SET v.createdAt = :yesterday WHERE v.candidate = :candidate'
        )
            ->setParameter('yesterday', new DateTimeImmutable('yesterday noon'))
            ->setParameter('candidate', $candidate)
            ->execute();
        $em->clear();
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
