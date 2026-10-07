<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\AnthropicCompanySectorClient;
use App\Domain\Vocabulary\CompanyToClassify;
use App\Domain\Vocabulary\VocabularyEnrichException;
use App\Enum\Sector;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * Adaptateur LLM de classification (task 105), client HTTP mocké : payload contraint (modèle +
 * secteur en enum), parsing des décisions, propagation des erreurs. Aucun appel Anthropic réel.
 */
final class AnthropicCompanySectorClientTest extends TestCase
{
    public function testSendsConstrainedPayloadAndParsesDecisions(): void
    {
        $captured = null;
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): JsonMockResponse {
            $captured = ['url' => $url, 'body' => json_decode((string) $options['body'], true)];

            $decisions = ['decisions' => [
                ['key' => 'lci-1', 'sector' => 'CONSEIL_AUDIT'],
                ['key' => 'name:freelance', 'sector' => 'INDEPENDANT'],
            ]];

            return new JsonMockResponse([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => json_encode($decisions)]],
            ]);
        });

        $decisions = (new AnthropicCompanySectorClient($http, 'claude-opus-4-8'))->classify([
            new CompanyToClassify('lci-1', 'Deloitte', 65),
            new CompanyToClassify('name:freelance', 'Freelance', 58),
        ]);

        self::assertStringEndsWith('/v1/messages', $captured['url']);
        self::assertSame('claude-opus-4-8', $captured['body']['model']);
        self::assertContains('CONSEIL_AUDIT', $captured['body']['output_config']['format']['schema']['properties']['decisions']['items']['properties']['sector']['enum']);

        self::assertCount(2, $decisions);
        self::assertSame('lci-1', $decisions[0]->key);
        self::assertSame(Sector::CONSEIL_AUDIT, $decisions[0]->sector);
        self::assertSame(Sector::INDEPENDANT, $decisions[1]->sector);
    }

    public function testUnknownSectorFallsBackToAutre(): void
    {
        $http = new MockHttpClient(new JsonMockResponse([
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => json_encode(['decisions' => [['key' => 'k', 'sector' => 'INEXISTANT']]])]],
        ]));

        $decisions = (new AnthropicCompanySectorClient($http, 'claude-opus-4-8'))
            ->classify([new CompanyToClassify('k', 'Machin', 3)]);

        self::assertSame(Sector::AUTRE, $decisions[0]->sector);
    }

    public function testThrowsOnRefusal(): void
    {
        $http = new MockHttpClient(new JsonMockResponse(['stop_reason' => 'refusal', 'content' => []]));

        $this->expectException(VocabularyEnrichException::class);

        (new AnthropicCompanySectorClient($http, 'claude-opus-4-8'))
            ->classify([new CompanyToClassify('k', 'Machin', 3)]);
    }

    public function testReturnsEmptyWithoutCallingApiWhenNoCompanies(): void
    {
        $http = new MockHttpClient(static function (): JsonMockResponse {
            throw new LogicException('Aucun appel HTTP ne doit partir sans entreprise.');
        });

        self::assertSame([], (new AnthropicCompanySectorClient($http, 'claude-opus-4-8'))->classify([]));
    }
}
