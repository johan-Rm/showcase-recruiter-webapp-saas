<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CreditWallet;
use App\Entity\Notification;
use App\Entity\Organization;
use App\Entity\Person;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Émission transactionnelle au dévoilement (tranche 2 de 058) : un accès payé
 * (EMAIL/PHONE/RESUME/INTERVIEW_REPORT) crée UNE notification `PROFILE_REVEALED` au candidat, dans la
 * même transaction que le dévoilement. L'anonymisation du payload du flux est couverte par
 * `NotificationTest` ; ici on vérifie l'émission en base après un dévoilement HTTP réel.
 */
final class NotificationEmissionTest extends ApiTestCase
{
    public function testRevealEmitsNotificationToCandidate(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->demoOrganizationAndCandidate();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);

        $notifications = $this->candidateNotifications();
        self::assertCount(1, $notifications);
        self::assertSame(NotificationType::PROFILE_REVEALED, $notifications[0]->type);
        self::assertSame(['access' => 'EMAIL'], $notifications[0]->data);
        // relatedOrganization renseignée en INTERNE (email à venir) ; le flux ne l'expose jamais
        // (cf. NotificationTest::testRecipientReadsOwnFluxOrderedAndAnonymised).
        self::assertNotNull($notifications[0]->relatedOrganization);
    }

    public function testEachAccessEmitsItsOwnNotification(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->demoOrganizationAndCandidate();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'RESUME']);
        self::assertResponseStatusCodeSame(201);

        $accesses = array_map(
            static fn (Notification $n): mixed => ((array) $n->data)['access'] ?? null,
            $this->candidateNotifications(),
        );
        self::assertCount(2, $accesses);
        self::assertContains('EMAIL', $accesses);
        self::assertContains('RESUME', $accesses);
    }

    public function testIdempotentRevealDoesNotDuplicateNotification(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->demoOrganizationAndCandidate();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);

        // Second appel idempotent : pas de re-débit (RevealTest), donc pas de seconde notification.
        self::assertCount(1, $this->candidateNotifications());
    }

    public function testInsufficientCreditsEmitsNoNotification(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->demoOrganizationAndCandidate();
        $this->drainWallet();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(402);

        // Dévoilement refusé (transaction non commitée) ⇒ aucune notification : l'émission est bien
        // solidaire du dévoilement (rollback commun).
        self::assertCount(0, $this->candidateNotifications());
    }

    /**
     * @return list<Notification> notifications du candidat de démo (El Amrani)
     */
    private function candidateNotifications(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($person);

        return $em->getRepository(Notification::class)->findBy(['recipient' => $person]);
    }

    /**
     * @return array{0: string, 1: string} [organizationUuid, candidateUuid]
     */
    private function demoOrganizationAndCandidate(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'showcase-recruiter-webapp-saas Demo SARL']);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($organization);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);

        return [$organization->getUuid()->toString(), $candidate->getUuid()->toString()];
    }

    private function drainWallet(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'showcase-recruiter-webapp-saas Demo SARL']);
        $wallet = $em->getRepository(CreditWallet::class)->findOneBy(['organization' => $organization]);
        self::assertNotNull($wallet);
        $wallet->balance = 0;
        $em->flush();
    }
}
