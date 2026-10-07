<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\PasswordSetupToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class PasswordSetupTest extends ApiTestCase
{
    /**
     * Le lien part à la fin du formulaire, et non au bout de la revue : c'est ce qui permet au
     * déclarant d'entrer sans attendre une décision humaine. Aucune validation n'intervient ici.
     */
    public function testRegistrationIssuesPasswordSetupTokenWithoutWaitingForReview(): void
    {
        $declarantToken = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $declarantToken, ['name' => 'Atlas Recrut', 'givenName' => 'Nadia', 'familyName' => 'Cherkaoui', 'acquisitionChannel' => 'LINKEDIN']);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('EN_REVUE', $this->jsonResponse()['validationStatus']);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $declarant = $em->getRepository(User::class)->findOneBy(['email' => 'nadia.cherkaoui@entreprise-abc.test']);

        self::assertInstanceOf(User::class, $declarant);
        self::assertInstanceOf(PasswordSetupToken::class, $em->getRepository(PasswordSetupToken::class)->findOneBy([
            'user' => $declarant,
        ]));
    }

    public function testPasswordSetupTokenCanBeConsumedOnceThenUserCanLogin(): void
    {
        $plainToken = 'valid-password-setup-token';
        $email = 'nadia.cherkaoui@entreprise-abc.test';
        $this->persistPasswordSetupToken($email, $plainToken, new DateTimeImmutable('+30 minutes'));

        $this->request('POST', '/v1/auth/password-setup/consume', null, [
            'token' => $plainToken,
            'password' => 'New-Password1',
        ]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['passwordSet']);

        $this->loginToken($email, 'New-Password1');

        $this->request('POST', '/v1/auth/password-setup/consume', null, [
            // Fort aussi : la validation du mot de passe passe AVANT le contrôle du token, sinon on
            // aurait un 422 (règles) au lieu du 410 (token déjà consommé) que ce cas vérifie.
            'token' => $plainToken,
            'password' => 'Another-Pass1',
        ]);
        self::assertResponseStatusCodeSame(410);
    }

    private function persistPasswordSetupToken(string $email, string $plainToken, DateTimeImmutable $expiresAt): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user);

        $em->persist(new PasswordSetupToken($user, hash('sha256', $plainToken), $expiresAt));
        $em->flush();
    }
}
