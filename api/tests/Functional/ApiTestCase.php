<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Base des tests fonctionnels : recrée le schéma de la base de test (showcase-recruiter-webapp-saas_test) et charge
 * les fixtures applicatives avant chaque test.
 */
abstract class ApiTestCase extends WebTestCase
{
    use ResetDatabase;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->loadFixtures();
    }

    /**
     * Requête JSON authentifiée (Accept: application/json).
     *
     * @param array<string, mixed>|null $payload
     */
    protected function request(string $method, string $uri, ?string $token = null, ?array $payload = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        if (null !== $payload) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        $this->client->request(
            $method,
            $uri,
            server: $server,
            content: null !== $payload ? json_encode($payload, \JSON_THROW_ON_ERROR) : null,
        );
    }

    /** @return array<string, mixed> */
    protected function jsonResponse(): array
    {
        $contentType = (string) $this->client->getResponse()->headers->get('Content-Type');
        self::assertStringContainsString('application/json', $contentType);

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    protected function loginToken(string $email = 'owner@demo.showcase-recruiter-webapp-saas.example', string $password = 'password'): string
    {
        $this->client->request(
            'POST',
            '/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        return (string) $payload['token'];
    }

    private function loadFixtures(): void
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $fixtures = $container->get(AppFixtures::class);

        $loader = new Loader();
        $loader->addFixture($fixtures);

        (new ORMExecutor($em, new ORMPurger($em)))->execute($loader->getFixtures());
    }
}
