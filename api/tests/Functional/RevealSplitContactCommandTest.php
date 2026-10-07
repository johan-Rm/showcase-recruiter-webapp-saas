<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Organization;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Conversion des dévoilements `CONTACT` hérités en `EMAIL` + `PHONE`.
 *
 * L'enjeu n'est pas technique, il est contractuel : une entreprise qui avait payé le contact ne
 * doit pas perdre son accès ni le racheter. Le test écrit une ligne héritée en SQL — la valeur
 * n'existe plus dans l'enum, donc Doctrine ne peut plus l'hydrater — puis lance la commande.
 */
final class RevealSplitContactCommandTest extends ApiTestCase
{
    public function testLegacyContactBecomesBothChannels(): void
    {
        [$organizationId, $candidateId, $recruiterId] = $this->ids();
        $this->insertLegacyContact($organizationId, $candidateId, $recruiterId);

        $tester = $this->splitContacts();

        self::assertStringContainsString('1 dévoilement(s) CONTACT', $tester->getDisplay());
        self::assertSame(
            ['EMAIL', 'PHONE'],
            $this->typesFor($organizationId, $candidateId),
            'Le contact payé doit valoir les deux canaux, sans nouveau débit.',
        );
    }

    /** Relancée, elle ne doit ni échouer ni dupliquer : on peut la rejouer sans crainte. */
    public function testCommandIsIdempotent(): void
    {
        [$organizationId, $candidateId, $recruiterId] = $this->ids();
        $this->insertLegacyContact($organizationId, $candidateId, $recruiterId);

        $this->splitContacts();
        $tester = $this->splitContacts();

        self::assertStringContainsString('Aucun dévoilement CONTACT hérité', $tester->getDisplay());
        self::assertSame(['EMAIL', 'PHONE'], $this->typesFor($organizationId, $candidateId));
    }

    /** `--dry-run` compte et n'écrit rien : la ligne héritée est toujours là après coup. */
    public function testDryRunWritesNothing(): void
    {
        [$organizationId, $candidateId, $recruiterId] = $this->ids();
        $this->insertLegacyContact($organizationId, $candidateId, $recruiterId);

        $this->splitContacts(['--dry-run' => true]);

        self::assertSame([], $this->typesFor($organizationId, $candidateId));
        self::assertSame(1, $this->legacyCount());
    }

    /** @param array<string, bool> $options */
    private function splitContacts(array $options = []): CommandTester
    {
        $application = new Application(self::$kernel ?? self::bootKernel());
        $tester = new CommandTester($application->find('app:reveals:split-contact'));
        $tester->execute($options);
        $tester->assertCommandIsSuccessful();

        return $tester;
    }

    /** @return array{int, int, int} */
    private function ids(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $organization = $em->getRepository(Organization::class)->findOneBy([]);
        $candidate = $em->getRepository(Candidate::class)->findOneBy([]);
        self::assertNotNull($organization);
        self::assertNotNull($candidate);

        $recruiterId = (int) $this->connection()->fetchOne('SELECT id FROM recruiter LIMIT 1');

        return [(int) $organization->getId(), (int) $candidate->getId(), $recruiterId];
    }

    private function insertLegacyContact(int $organizationId, int $candidateId, int $recruiterId): void
    {
        $this->connection()->executeStatement(
            'INSERT INTO revealed_profile (uuid, organization_id, candidate_id, revealed_by_id, type, created_at, updated_at)
             VALUES (gen_random_uuid(), :org, :candidate, :by, :type, NOW(), NOW())',
            ['org' => $organizationId, 'candidate' => $candidateId, 'by' => $recruiterId, 'type' => 'CONTACT'],
        );
    }

    /** @return list<string> */
    private function typesFor(int $organizationId, int $candidateId): array
    {
        $types = $this->connection()->fetchFirstColumn(
            "SELECT type FROM revealed_profile
             WHERE organization_id = :org AND candidate_id = :candidate AND type <> 'CONTACT'
             ORDER BY type",
            ['org' => $organizationId, 'candidate' => $candidateId],
        );

        return array_map(strval(...), $types);
    }

    private function legacyCount(): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM revealed_profile WHERE type = 'CONTACT'");
    }

    private function connection(): Connection
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        return $em->getConnection();
    }
}
