<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Recruiter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Assignation entreprise V1 (tâche 029) : un responsable par candidat dévoilé.
 */
final class AssignmentTest extends ApiTestCase
{
    public function testCompanyMemberAssignsRevealedCandidateToResponsible(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();

        $this->request('PUT', "/v1/organizations/{$ids['organizationId']}/assignments/{$ids['revealedCandidateId']}", $token, [
            'assigneeRecruiterId' => $ids['ownerRecruiterId'],
        ]);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame($ids['revealedCandidateId'], $payload['candidateId']);
        self::assertSame($ids['ownerRecruiterId'], $payload['assigneeRecruiterId']);

        $this->request('GET', "/v1/organizations/{$ids['organizationId']}/assignments", $token);
        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $this->jsonResponse()['total']);
    }

    public function testReassignmentReplacesResponsible(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();
        $uri = "/v1/organizations/{$ids['organizationId']}/assignments/{$ids['revealedCandidateId']}";

        $this->request('PUT', $uri, $token, ['assigneeRecruiterId' => $ids['ownerRecruiterId']]);
        self::assertResponseIsSuccessful();
        $this->request('PUT', $uri, $token, ['assigneeRecruiterId' => $ids['memberRecruiterId']]);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/organizations/{$ids['organizationId']}/assignments", $token);
        $assignments = $this->jsonResponse()['items'];
        $matching = array_values(array_filter(
            $assignments,
            static fn (array $assignment): bool => $assignment['candidateId'] === $ids['revealedCandidateId'],
        ));
        self::assertCount(1, $matching);
        self::assertSame($ids['memberRecruiterId'], $matching[0]['assigneeRecruiterId']);
    }

    public function testCannotAssignNonRevealedCandidate(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();

        $this->request('PUT', "/v1/organizations/{$ids['organizationId']}/assignments/{$ids['nonRevealedCandidateId']}", $token, [
            'assigneeRecruiterId' => $ids['ownerRecruiterId'],
        ]);
        self::assertResponseStatusCodeSame(409);
    }

    public function testCannotAssignToRecruiterOutsideOrganization(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();

        $this->request('PUT', "/v1/organizations/{$ids['organizationId']}/assignments/{$ids['revealedCandidateId']}", $token, [
            'assigneeRecruiterId' => $ids['externalRecruiterId'],
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testDeleteAssignment(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();
        $uri = "/v1/organizations/{$ids['organizationId']}/assignments/{$ids['revealedCandidateId']}";

        $this->request('PUT', $uri, $token, ['assigneeRecruiterId' => $ids['memberRecruiterId']]);
        self::assertResponseIsSuccessful();
        $this->request('DELETE', $uri, $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', "/v1/organizations/{$ids['organizationId']}/assignments", $token);
        $assignments = $this->jsonResponse()['items'];
        $matching = array_values(array_filter(
            $assignments,
            static fn (array $assignment): bool => $assignment['candidateId'] === $ids['revealedCandidateId'],
        ));
        self::assertCount(0, $matching);
    }

    public function testCrossTenantCannotListAssignments(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        $ids = $this->atlasIds();

        $this->request('GET', "/v1/organizations/{$ids['organizationId']}/assignments", $token);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{
     *     organizationId: string,
     *     revealedCandidateId: string,
     *     nonRevealedCandidateId: string,
     *     ownerRecruiterId: string,
     *     memberRecruiterId: string,
     *     externalRecruiterId: string
     * }
     */
    private function atlasIds(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'Atlas Talent Conseil']);
        $revealedCandidate = $this->candidateByFamilyName($em, 'Tazi');
        $nonRevealedCandidate = $this->candidateByFamilyName($em, 'El Amrani');
        $ownerRecruiter = $this->recruiterByEmail($em, 'atlas.owner@demo.showcase-recruiter-webapp-saas.example');
        $memberRecruiter = $this->recruiterByEmail($em, 'atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $externalRecruiter = $this->recruiterByEmail($em, 'owner@demo.showcase-recruiter-webapp-saas.example');

        self::assertNotNull($organization);

        return [
            'organizationId' => $organization->getUuid()->toString(),
            'revealedCandidateId' => $revealedCandidate->getUuid()->toString(),
            'nonRevealedCandidateId' => $nonRevealedCandidate->getUuid()->toString(),
            'ownerRecruiterId' => $ownerRecruiter->getUuid()->toString(),
            'memberRecruiterId' => $memberRecruiter->getUuid()->toString(),
            'externalRecruiterId' => $externalRecruiter->getUuid()->toString(),
        ];
    }

    private function candidateByFamilyName(EntityManagerInterface $em, string $familyName): Candidate
    {
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => $familyName]);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);

        return $candidate;
    }

    private function recruiterByEmail(EntityManagerInterface $em, string $email): Recruiter
    {
        $person = $em->getRepository(Person::class)->findOneBy(['contactEmail' => $email]);
        self::assertNotNull($person);
        $recruiter = $em->getRepository(Recruiter::class)->findOneBy(['person' => $person]);
        self::assertNotNull($recruiter);

        return $recruiter;
    }
}
