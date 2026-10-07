<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Doctrine\Filter\SoftDeleteFilter;
use App\Entity\Candidate;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Soft delete : `softDelete()` doit avoir un EFFET, pas seulement horodater une colonne.
 *
 * Avant le filtre Doctrine, une entité « supprimée » restait visible partout — listes, recherche,
 * relations. C'est exactement le genre de faux plancher qu'un test doit verrouiller.
 */
final class SoftDeleteTest extends ApiTestCase
{
    public function testSoftDeletedCandidateLeavesTheSearch(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');

        $this->request('GET', '/v1/search/candidates', $token);
        self::assertResponseIsSuccessful();
        $before = $this->jsonResponse()['total'];
        self::assertGreaterThan(0, $before);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $em->getRepository(Candidate::class)->findOneBy([]);
        self::assertNotNull($candidate);
        $candidateId = $candidate->getUuid()->toString();
        $candidate->softDelete();
        $em->flush();

        $this->request('GET', '/v1/search/candidates', $token);
        self::assertResponseIsSuccessful();
        self::assertSame($before - 1, $this->jsonResponse()['total']);

        // 404, pas 403 : un candidat supprimé n'existe plus, il n'est pas « interdit ».
        $this->request('GET', "/v1/candidates/{$candidateId}", $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testSoftDeletedOrganizationLeavesTheAdminReviewQueue(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, [
            'name' => 'Atlas Recrut',
            'givenName' => 'Nadia',
            'familyName' => 'Cherkaoui',
            'acquisitionChannel' => 'LINKEDIN',
        ]);
        $organizationId = $this->jsonResponse()['id'];

        $adminToken = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', '/v1/admin/organizations?status=EN_REVUE', $adminToken);
        self::assertContains($organizationId, array_column($this->jsonResponse()['items'], 'id'));

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'Atlas Recrut']);
        self::assertNotNull($organization);
        $organization->softDelete();
        $em->flush();

        $this->request('GET', '/v1/admin/organizations?status=EN_REVUE', $adminToken);
        self::assertNotContains($organizationId, array_column($this->jsonResponse()['items'], 'id'));

        $this->request('GET', "/v1/organizations/{$organizationId}", $adminToken);
        self::assertResponseStatusCodeSame(404);
    }

    /** Le filtre se désactive explicitement — sinon une purge RGPD ne pourrait plus voir ce qu'elle purge. */
    public function testDisablingTheFilterBringsSoftDeletedRecordsBack(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $candidate = $em->getRepository(Candidate::class)->findOneBy([]);
        self::assertNotNull($candidate);
        $jarviProfileId = $candidate->jarviProfileId;
        $candidate->softDelete();
        $em->flush();
        $em->clear();

        self::assertNull($em->getRepository(Candidate::class)->findOneBy(['jarviProfileId' => $jarviProfileId]));

        $em->getFilters()->disable(SoftDeleteFilter::NAME);
        $revealed = $em->getRepository(Candidate::class)->findOneBy(['jarviProfileId' => $jarviProfileId]);

        self::assertNotNull($revealed);
        self::assertTrue($revealed->isDeleted());
    }
}
