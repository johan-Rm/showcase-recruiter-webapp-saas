<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ValidationRulesTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testAuthLoginRulesArePublic(): void
    {
        $this->client->request('GET', '/v1/validation-rules/authLogin', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame('authLogin', $payload['form']);
        self::assertSame('email', self::rule($payload['fields']['email'], 'email')['type']);
        self::assertSame(255, self::rule($payload['fields']['password'], 'length')['max']);
    }

    public function testOrganizationRulesExposeCreateGroup(): void
    {
        $this->client->request('GET', '/v1/validation-rules/organization', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame('organization', $payload['form']);
        self::assertContains('create', self::rule($payload['fields']['givenName'], 'notBlank')['groups']);
        self::assertTrue(self::rule($payload['fields']['website'], 'url')['requireTld']);
    }

    public function testPresencePeriodRulesExposeCrossFieldConstraint(): void
    {
        $this->client->request('GET', '/v1/validation-rules/presencePeriod', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame('presencePeriod', $payload['form']);
        self::assertSame('startDate', self::rule($payload['fields']['endDate'], 'greaterThanOrEqual')['propertyPath']);
    }

    public function testUnknownFormReturnsNotFound(): void
    {
        $this->client->request('GET', '/v1/validation-rules/unknown', server: ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(404);
    }

    /** @return array<string, mixed> */
    private function jsonResponse(): array
    {
        $contentType = (string) $this->client->getResponse()->headers->get('Content-Type');
        self::assertStringContainsString('application/json', $contentType);

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<array<string, mixed>> $rules
     *
     * @return array<string, mixed>
     */
    private static function rule(array $rules, string $type): array
    {
        foreach ($rules as $rule) {
            if (($rule['type'] ?? null) === $type) {
                return $rule;
            }
        }

        self::fail(\sprintf('Rule "%s" was not exposed.', $type));
    }
}
