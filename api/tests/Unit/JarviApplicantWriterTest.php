<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Jarvi\JarviApplicant;
use App\Domain\Jarvi\JarviApplicantWriter;
use App\Domain\Jarvi\JarviResumeFile;
use App\Domain\Jarvi\JarviWriteException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * Canal `/applicants` (tâche 088), client HTTP mocké : payload URL seule, payload avec CV, et
 * réponse d'erreur. Aucun appel Jarvi réel ; la clé n'est jamais exposée.
 */
final class JarviApplicantWriterTest extends TestCase
{
    public function testSubmitsLinkedinOnlyPayloadAndReturnsAnchor(): void
    {
        $captured = null;
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): JsonMockResponse {
            $captured = ['method' => $method, 'url' => $url, 'body' => json_decode((string) $options['body'], true)];

            return new JsonMockResponse(['message' => 'ok', 'taskId' => 'task-123', 'profileId' => 'profile-abc', 'fileErrors' => []], ['http_code' => 201]);
        });

        $result = (new JarviApplicantWriter($client))->submit(new JarviApplicant(
            externalId: 'ext-1',
            firstName: 'Nadia',
            lastName: 'Berrada',
            projectId: 'project-vivier',
            linkedinUrl: 'https://www.linkedin.com/in/nadia',
        ));

        self::assertSame('profile-abc', $result->profileId);
        self::assertSame('task-123', $result->taskId);
        self::assertSame([], $result->fileErrors);

        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertStringEndsWith('/applicants', $captured['url']);
        self::assertSame('ext-1', $captured['body']['externalId']);
        self::assertSame('project-vivier', $captured['body']['projectId']);
        self::assertSame('https://www.linkedin.com/in/nadia', $captured['body']['linkedinUrl']);
        // Aucun CV → pas de clé resumesFiles dans le payload.
        self::assertArrayNotHasKey('resumesFiles', $captured['body']);
    }

    public function testSubmitsResumePayload(): void
    {
        $captured = null;
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): JsonMockResponse {
            $captured = json_decode((string) $options['body'], true);

            return new JsonMockResponse(['profileId' => 'profile-xyz', 'taskId' => 't', 'fileErrors' => []], ['http_code' => 201]);
        });

        (new JarviApplicantWriter($client))->submit(new JarviApplicant(
            externalId: 'ext-2',
            firstName: 'Sami',
            lastName: 'Haddad',
            projectId: 'project-vivier',
            resumesFiles: [new JarviResumeFile('cv.pdf', 'data:@file/pdf;base64,QUJD')],
        ));

        self::assertNotNull($captured);
        self::assertCount(1, $captured['resumesFiles']);
        self::assertSame('cv.pdf', $captured['resumesFiles'][0]['fileName']);
        self::assertSame('data:@file/pdf;base64,QUJD', $captured['resumesFiles'][0]['data']);
    }

    public function testHttpErrorRaisesTypedException(): void
    {
        $client = new MockHttpClient(new JsonMockResponse(['message' => 'bad request'], ['http_code' => 400]));

        $this->expectException(JarviWriteException::class);
        (new JarviApplicantWriter($client))->submit(new JarviApplicant(
            externalId: 'ext-3',
            firstName: 'Err',
            lastName: 'Or',
            projectId: 'project-vivier',
            linkedinUrl: 'https://www.linkedin.com/in/err',
        ));
    }

    public function testMissingProfileIdRaisesTypedException(): void
    {
        $client = new MockHttpClient(new JsonMockResponse(['message' => 'ok', 'taskId' => 't'], ['http_code' => 201]));

        $this->expectException(JarviWriteException::class);
        (new JarviApplicantWriter($client))->submit(new JarviApplicant(
            externalId: 'ext-4',
            firstName: 'No',
            lastName: 'Id',
            projectId: 'project-vivier',
            linkedinUrl: 'https://www.linkedin.com/in/noid',
        ));
    }
}
