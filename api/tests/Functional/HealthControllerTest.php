<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthIsPublicAndReturnsOk(): void
    {
        $client = self::createClient();
        $client->request('GET', '/v1/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('ok', $payload['status']);
        self::assertSame('showcase-recruiter-webapp-saas-api', $payload['service']);
    }
}
