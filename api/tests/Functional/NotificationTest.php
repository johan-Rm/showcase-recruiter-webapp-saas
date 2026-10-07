<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Notification;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Flux de notifications à curseur + marquage lu (tâche 058). Lecture self-only, curseur uuid v7,
 * enveloppe `{ items, nextCursor }`, payload anonymisé (aucune identité d'entreprise). L'émission
 * transactionnelle (dévoilement, favori) relève des tranches suivantes ; ici on sème directement.
 */
final class NotificationTest extends ApiTestCase
{
    public function testRecipientReadsOwnFluxOrderedAndAnonymised(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('notif.self@example.com');
        $this->seedNotifications($candidateId, [
            NotificationType::PROFILE_REVEALED,
            NotificationType::NEW_QUALIFIED_PROFILE,
        ]);

        $this->request('GET', '/v1/notifications', $token);
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertCount(2, (array) $body['items']);
        // Tri chronologique (uuid v7 croissant) = ordre d'insertion.
        self::assertSame('PROFILE_REVEALED', $body['items'][0]['type']);
        self::assertSame('NEW_QUALIFIED_PROFILE', $body['items'][1]['type']);
        self::assertNull($body['items'][0]['readAt']);
        // Curseur = uuid de la dernière notification du lot.
        self::assertSame($body['items'][1]['id'], $body['nextCursor']);

        // Anonymisation : aucune identité d'entreprise ni destinataire ne fuite du payload.
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('organization', $content);
        self::assertStringNotContainsString('recipient', $content);
    }

    public function testCursorReturnsOnlyNewer(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('notif.cursor@example.com');
        $this->seedNotifications($candidateId, [
            NotificationType::PROFILE_REVEALED,
            NotificationType::PROFILE_IN_SEARCHES,
            NotificationType::NEW_QUALIFIED_PROFILE,
        ]);

        $this->request('GET', '/v1/notifications', $token);
        $all = $this->jsonResponse();
        self::assertCount(3, (array) $all['items']);

        $firstId = (string) $all['items'][0]['id'];
        $this->request('GET', "/v1/notifications?after={$firstId}", $token);
        $after = $this->jsonResponse();
        self::assertCount(2, (array) $after['items']);
        self::assertSame($all['items'][1]['id'], $after['items'][0]['id']);
        self::assertSame($all['items'][2]['id'], $after['items'][1]['id']);
    }

    public function testInvalidCursorIsRejected(): void
    {
        [$token] = $this->onboardAndLogin('notif.badcursor@example.com');

        $this->request('GET', '/v1/notifications?after=not-a-uuid', $token);
        self::assertResponseStatusCodeSame(400);
    }

    public function testBrandNewRecipientHasEmptyFlux(): void
    {
        [$token] = $this->onboardAndLogin('notif.empty@example.com');

        $this->request('GET', '/v1/notifications', $token);
        self::assertResponseIsSuccessful();

        $body = $this->jsonResponse();
        self::assertSame([], (array) $body['items']);
        self::assertNull($body['nextCursor']);
    }

    public function testFluxIsSelfScoped(): void
    {
        [, $candidateA] = $this->onboardAndLogin('notif.scope.a@example.com');
        $this->seedNotifications($candidateA, [NotificationType::PROFILE_REVEALED]);
        [$tokenB] = $this->onboardAndLogin('notif.scope.b@example.com');

        // B ne voit jamais les notifications de A : la route n'expose que les siennes.
        $this->request('GET', '/v1/notifications', $tokenB);
        self::assertResponseIsSuccessful();
        self::assertSame([], (array) $this->jsonResponse()['items']);
    }

    public function testMarkOneAsRead(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('notif.read@example.com');
        $ids = $this->seedNotifications($candidateId, [NotificationType::PROFILE_REVEALED]);

        $this->request('POST', "/v1/notifications/{$ids[0]}/read", $token);
        self::assertResponseIsSuccessful();
        self::assertNotNull($this->jsonResponse()['readAt']);

        $this->request('GET', '/v1/notifications', $token);
        self::assertNotNull($this->jsonResponse()['items'][0]['readAt']);
    }

    public function testCannotMarkAnothersNotification(): void
    {
        [, $candidateA] = $this->onboardAndLogin('notif.reada@example.com');
        $ids = $this->seedNotifications($candidateA, [NotificationType::PROFILE_REVEALED]);
        [$tokenB] = $this->onboardAndLogin('notif.readb@example.com');

        // 404, pas 403 : l'existence d'une notification d'autrui ne se déduit pas.
        $this->request('POST', "/v1/notifications/{$ids[0]}/read", $tokenB);
        self::assertResponseStatusCodeSame(404);
    }

    public function testMarkAllAsRead(): void
    {
        [$token, $candidateId] = $this->onboardAndLogin('notif.readall@example.com');
        $this->seedNotifications($candidateId, [
            NotificationType::PROFILE_REVEALED,
            NotificationType::NEW_QUALIFIED_PROFILE,
        ]);

        $this->request('POST', '/v1/notifications/read-all', $token);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', '/v1/notifications', $token);
        foreach ((array) $this->jsonResponse()['items'] as $notification) {
            self::assertNotNull($notification['readAt']);
        }
    }

    /**
     * @param list<NotificationType> $types
     *
     * @return list<string> uuids créés, dans l'ordre d'insertion
     */
    private function seedNotifications(string $candidateUuid, array $types): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['uuid' => $candidateUuid]);
        self::assertInstanceOf(Candidate::class, $candidate);
        $recipient = $candidate->getPerson();

        $ids = [];
        foreach ($types as $type) {
            $notification = new Notification($recipient, $type);
            $em->persist($notification);
            $ids[] = (string) $notification->getUuid();
        }
        $em->flush();

        return $ids;
    }

    /**
     * @return array{0: string, 1: string} [token, candidateId]
     */
    private function onboardAndLogin(string $email): array
    {
        $this->request('POST', '/v1/candidates/onboarding', null, [
            'email' => $email,
            'password' => 'password123',
            'givenName' => 'Test',
            'familyName' => 'Candidat',
            'linkedinUrl' => 'https://www.linkedin.com/in/test-candidat',
        ]);
        self::assertResponseStatusCodeSame(201);
        $candidateId = (string) $this->jsonResponse()['candidateId'];

        return [$this->loginToken($email, 'password123'), $candidateId];
    }
}
