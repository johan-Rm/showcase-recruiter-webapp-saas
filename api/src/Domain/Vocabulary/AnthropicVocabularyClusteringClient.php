<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\VocabularyClusterAction;
use App\Enum\VocabularyType;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adaptateur LLM du {@see VocabularyClusteringClient} : une requête Messages API par lot (le
 * découpage est décidé en amont par l'enricher). La sortie est contrainte par un schéma JSON
 * (structured outputs) → on parse un tableau de décisions, pas du texte libre. La clé vit côté
 * `api/` uniquement, jamais exposée au webapp ; en test on injecte un MockHttpClient.
 *
 * Le LLM ne tourne QUE dans cette commande d'enrichissement (rare, manuelle) — jamais dans le
 * pipeline quotidien harvest + import.
 */
final readonly class AnthropicVocabularyClusteringClient implements VocabularyClusteringClient
{
    private const ENDPOINT = 'v1/messages';

    public function __construct(
        private HttpClientInterface $anthropicClient,
        private string $model,
        private int $maxTokens = 8192,
    ) {
    }

    public function cluster(VocabularyType $type, array $terms, array $existingConcepts): array
    {
        if ([] === $terms) {
            return [];
        }

        try {
            $data = $this->anthropicClient->request('POST', self::ENDPOINT, [
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'system' => $this->systemPrompt($type),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->userPrompt($terms, $existingConcepts)],
                    ],
                    'output_config' => ['format' => $this->schema()],
                ],
                'timeout' => 180,
            ])->toArray();
        } catch (HttpExceptionInterface $e) {
            throw new VocabularyEnrichException('Appel Anthropic échoué : '.$e->getMessage(), previous: $e);
        }

        if ('refusal' === ($data['stop_reason'] ?? null)) {
            throw new VocabularyEnrichException('Le modèle a refusé la requête de clustering.');
        }

        return $this->parseDecisions($data);
    }

    private function systemPrompt(VocabularyType $type): string
    {
        $nature = VocabularyType::COMPETENCE === $type ? 'compétences' : 'postes / métiers';

        return <<<PROMPT
            Tu ranges des libellés bruts de {$nature} (issus de profils, en français ou anglais) dans une
            taxonomie. Pour chaque terme fourni, choisis UNE action :
            - ATTACH : le terme est un synonyme d'un concept EXISTANT (fourni dans la liste). Renvoie son
              libellé canonique EXACT dans concept_label.
            - NEW : le terme désigne une compétence/un métier réel absent de la liste. Renvoie dans
              concept_label un libellé canonique propre (français de préférence, correctement orthographié).
              Regroupe sous le MÊME concept_label des termes qui désignent la même idée.
            - NOISE : fragment de phrase, terme trop générique, illisible ou vide → à écarter.
            Détecte la langue du terme (fr, en ou ar). Réponds uniquement via le schéma imposé, une
            décision par terme fourni.
            PROMPT;
    }

    /**
     * @param list<TermToCluster> $terms
     * @param list<string>        $existingConcepts
     */
    private function userPrompt(array $terms, array $existingConcepts): string
    {
        $concepts = [] === $existingConcepts ? '(aucun)' : implode("\n", array_map(
            static fn (string $label): string => '- '.$label,
            $existingConcepts,
        ));
        $lines = implode("\n", array_map(
            static fn (TermToCluster $t): string => \sprintf('- %s (fréquence %d)', $t->term, $t->frequency),
            $terms,
        ));

        return <<<PROMPT
            Concepts existants :
            {$concepts}

            Termes à ranger :
            {$lines}
            PROMPT;
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
                            'required' => ['term', 'action', 'concept_label', 'lang'],
                            'properties' => [
                                'term' => ['type' => 'string'],
                                'action' => ['type' => 'string', 'enum' => ['ATTACH', 'NEW', 'NOISE']],
                                'concept_label' => ['type' => 'string'],
                                'lang' => ['type' => 'string', 'enum' => ['fr', 'en', 'ar']],
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
     * @return list<ClusterDecision>
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
            $action = VocabularyClusterAction::tryFrom((string) ($raw['action'] ?? '')) ?? VocabularyClusterAction::NOISE;
            $decisions[] = new ClusterDecision(
                (string) ($raw['term'] ?? ''),
                $action,
                (string) ($raw['concept_label'] ?? ''),
                (string) ($raw['lang'] ?? 'fr'),
            );
        }

        return $decisions;
    }
}
