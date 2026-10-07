<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adaptateur LLM du {@see SchoolGroupingClient} : une requête Messages API par lot. Contrairement
 * aux secteurs, le nom canonique n'est PAS un enum — un établissement se ramène à sa forme de
 * référence en texte libre. On demande donc au modèle de réutiliser à l'identique un nom canonique
 * déjà émis dans le lot lorsqu'il reconnaît le même établissement, pour que les graphies fusionnent.
 *
 * La clé vit côté `api/` uniquement ; en test on injecte un MockHttpClient. Le LLM ne tourne que
 * dans app:school:group — commande de surcroît désactivée par défaut.
 */
final readonly class AnthropicSchoolGroupingClient implements SchoolGroupingClient
{
    private const ENDPOINT = 'v1/messages';

    public function __construct(
        private HttpClientInterface $anthropicClient,
        private string $model,
        private int $maxTokens = 8192,
    ) {
    }

    public function group(array $schools): array
    {
        if ([] === $schools) {
            return [];
        }

        try {
            $data = $this->anthropicClient->request('POST', self::ENDPOINT, [
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->userPrompt($schools)],
                    ],
                    'output_config' => ['format' => $this->schema()],
                ],
                'timeout' => 180,
            ])->toArray();
        } catch (HttpExceptionInterface $e) {
            throw new VocabularyEnrichException('Appel Anthropic échoué : '.$e->getMessage(), previous: $e);
        }

        if ('refusal' === ($data['stop_reason'] ?? null)) {
            throw new VocabularyEnrichException('Le modèle a refusé la requête de regroupement.');
        }

        return $this->parseDecisions($data);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            Tu ramènes des noms d'établissements d'enseignement à leur nom CANONIQUE, pour fusionner les
            graphies et abréviations d'un même établissement. Exemples : « ENCG Casablanca » et « École
            Nationale de Commerce et de Gestion de Casablanca » → un seul nom canonique ; « EMSI » et
            « École Marocaine des Sciences de l'Ingénieur » → un seul.
            Règles : quand plusieurs entrées du lot désignent le MÊME établissement, réutilise pour
            toutes le MÊME `canonicalName`, à l'identique caractère pour caractère. Préfère le nom
            complet officiel plutôt que le sigle. Si un nom est illisible, générique ou non
            rattachable, reprends-le tel quel comme `canonicalName`. Réponds uniquement via le schéma
            imposé, une décision par établissement fourni, en reprenant sa `key`.
            PROMPT;
    }

    /**
     * @param list<HarvestedSchool> $schools
     */
    private function userPrompt(array $schools): string
    {
        $lines = implode("\n", array_map(
            static fn (HarvestedSchool $s): string => \sprintf('- key=%s | %s (fréquence %d)', $s->key, $s->name, $s->frequency),
            $schools,
        ));

        return "Établissements à regrouper :\n".$lines;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['decisions'],
                'properties' => [
                    'decisions' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['key', 'canonicalName'],
                            'properties' => [
                                'key' => ['type' => 'string'],
                                'canonicalName' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<SchoolGroupingDecision>
     */
    private function parseDecisions(array $data): array
    {
        $text = null;
        foreach (($data['content'] ?? []) as $block) {
            if (\is_array($block) && 'text' === ($block['type'] ?? null)) {
                $text = (string) ($block['text'] ?? '');
                break;
            }
        }
        if (null === $text) {
            throw new VocabularyEnrichException('Réponse Anthropic sans bloc texte exploitable.');
        }

        $payload = json_decode($text, true);
        if (!\is_array($payload) || !\is_array($payload['decisions'] ?? null)) {
            throw new VocabularyEnrichException('JSON de décisions invalide dans la réponse Anthropic.');
        }

        $decisions = [];
        foreach ($payload['decisions'] as $raw) {
            if (!\is_array($raw)) {
                continue;
            }
            $key = (string) ($raw['key'] ?? '');
            $canonicalName = trim((string) ($raw['canonicalName'] ?? ''));
            if ('' === $key || '' === $canonicalName) {
                continue;
            }
            $decisions[] = new SchoolGroupingDecision($key, $canonicalName);
        }

        return $decisions;
    }
}
