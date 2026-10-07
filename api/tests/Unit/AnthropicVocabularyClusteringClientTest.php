<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Vocabulary\AnthropicVocabularyClusteringClient;
use App\Domain\Vocabulary\TermToCluster;
use App\Domain\Vocabulary\VocabularyEnrichException;
use App\Enum\VocabularyClusterAction;
use App\Enum\VocabularyType;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

/**
 * Adaptateur LLM (tâche 096), client HTTP mocké : payload conforme (modèle + schéma contraint),
 * parsing des décisions, et propagation des erreurs (refus, HTTP). Aucun appel Anthropic réel ;
 * la clé n'est jamais exposée.
 */
final class AnthropicVocabularyClusteringClientTest extends TestCase
{
    public function testSendsConstrainedPayloadAndParsesDecisions(): void
    {
        $captured = null;
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): JsonMockResponse {
            $captured = ['method' => $method, 'url' => $url, 'body' => json_decode((string) $options['body'], true)];

            $decisions = ['decisions' => [
                ['term' => 'project management', 'action' => 'ATTACH', 'concept_label' => 'Gestion de projet', 'lang' => 'en'],
                ['term' => 'k8s', 'action' => 'NEW', 'concept_label' => 'Kubernetes', 'lang' => 'en'],
            ]];

            return new JsonMockResponse([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => json_encode($decisions)]],
            ]);
        });

        $decisions = (new AnthropicVocabularyClusteringClient($http, 'claude-opus-4-8'))->cluster(
            VocabularyType::COMPETENCE,
            [new TermToCluster('project management', 233), new TermToCluster('k8s', 10)],
            ['Gestion de projet'],
        );

        self::assertSame('POST', $captured['method']);
        self::assertStringEndsWith('/v1/messages', $captured['url']);
        self::assertSame('claude-opus-4-8', $captured['body']['model']);
        self::assertSame('json_schema', $captured['body']['output_config']['format']['type']);

        self::assertCount(2, $decisions);
        self::assertSame(VocabularyClusterAction::ATTACH, $decisions[0]->action);
        self::assertSame('Gestion de projet', $decisions[0]->conceptLabel);
        self::assertSame(VocabularyClusterAction::NEW, $decisions[1]->action);
        self::assertSame('en', $decisions[1]->lang);
    }

    public function testThrowsOnRefusal(): void
    {
        $http = new MockHttpClient(new JsonMockResponse(['stop_reason' => 'refusal', 'content' => []]));

        $this->expectException(VocabularyEnrichException::class);

        (new AnthropicVocabularyClusteringClient($http, 'claude-opus-4-8'))->cluster(
            VocabularyType::COMPETENCE,
            [new TermToCluster('anything', 5)],
            [],
        );
    }

    public function testThrowsOnHttpError(): void
    {
        $http = new MockHttpClient(new JsonMockResponse(['error' => ['message' => 'bad request']], ['http_code' => 400]));

        $this->expectException(VocabularyEnrichException::class);

        (new AnthropicVocabularyClusteringClient($http, 'claude-opus-4-8'))->cluster(
            VocabularyType::COMPETENCE,
            [new TermToCluster('anything', 5)],
            [],
        );
    }

    public function testReturnsEmptyWithoutCallingApiWhenNoTerms(): void
    {
        $http = new MockHttpClient(static function (): JsonMockResponse {
            throw new LogicException('Aucun appel HTTP ne doit partir sans terme.');
        });

        self::assertSame([], (new AnthropicVocabularyClusteringClient($http, 'claude-opus-4-8'))->cluster(
            VocabularyType::COMPETENCE,
            [],
            [],
        ));
    }
}
