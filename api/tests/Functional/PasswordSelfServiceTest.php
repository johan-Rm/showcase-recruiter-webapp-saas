<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class PasswordSelfServiceTest extends ApiTestCase
{
    public function testAuthenticatedUserCanChangePassword(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');

        $this->request('PUT', '/v1/me/password', $token, [
            'currentPassword' => 'password',
            'newPassword' => 'new-password',
        ]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['passwordChanged']);

        $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example', 'new-password');
    }

    public function testChangingPasswordRequiresCurrentPassword(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');

        $this->request('PUT', '/v1/me/password', $token, [
            'currentPassword' => 'wrong-password',
            'newPassword' => 'new-password',
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testPasswordResetRequestIsAntiEnumeration(): void
    {
        $this->request('POST', '/v1/auth/password-reset/request', null, [
            'email' => 'unknown@demo.showcase-recruiter-webapp-saas.example',
        ]);
        self::assertResponseStatusCodeSame(202);
        self::assertTrue($this->jsonResponse()['accepted']);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertSame(0, $em->getRepository(PasswordResetToken::class)->count([]));
    }

    public function testPasswordResetRequestCreatesTokenForKnownUser(): void
    {
        $this->request('POST', '/v1/auth/password-reset/request', null, [
            'email' => 'owner@demo.showcase-recruiter-webapp-saas.example',
        ]);
        self::assertResponseStatusCodeSame(202);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'owner@demo.showcase-recruiter-webapp-saas.example']);

        self::assertInstanceOf(User::class, $user);
        self::assertInstanceOf(PasswordResetToken::class, $em->getRepository(PasswordResetToken::class)->findOneBy([
            'user' => $user,
        ]));
    }

    public function testPasswordResetTokenCanBeConsumedOnceThenUserCanLogin(): void
    {
        $plainToken = 'valid-password-reset-token';
        $email = 'owner@demo.showcase-recruiter-webapp-saas.example';
        $this->persistPasswordResetToken($email, $plainToken, new DateTimeImmutable('+30 minutes'));

        $this->request('POST', '/v1/auth/password-reset/consume', null, [
            'token' => $plainToken,
            'newPassword' => 'new-password',
        ]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['accepted']);

        $this->loginToken($email, 'new-password');

        $this->request('POST', '/v1/auth/password-reset/consume', null, [
            'token' => $plainToken,
            'newPassword' => 'another-password',
        ]);
        self::assertResponseStatusCodeSame(410);
    }

    public function testExpiredPasswordResetTokenIsRejected(): void
    {
        $plainToken = 'expired-password-reset-token';
        $this->persistPasswordResetToken('owner@demo.showcase-recruiter-webapp-saas.example', $plainToken, new DateTimeImmutable('-1 minute'));

        $this->request('POST', '/v1/auth/password-reset/consume', null, [
            'token' => $plainToken,
            'newPassword' => 'new-password',
        ]);
        self::assertResponseStatusCodeSame(410);
    }

    private function persistPasswordResetToken(string $email, string $plainToken, DateTimeImmutable $expiresAt): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        $em->persist(new PasswordResetToken($user, hash('sha256', $plainToken), $expiresAt));
        $em->flush();
    }
}
