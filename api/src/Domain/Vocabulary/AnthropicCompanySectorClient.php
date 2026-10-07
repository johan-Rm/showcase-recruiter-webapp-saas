<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\Sector;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adaptateur LLM du {@see CompanySectorClient} : une requête Messages API par lot (le découpage est
 * décidé par le classifier). La sortie est contrainte par un schéma JSON dont le secteur est un
 * `enum` — on ne récupère jamais un secteur hors nomenclature. La clé vit côté `api/` uniquement ;
 * en test on injecte un MockHttpClient. Le LLM ne tourne QUE dans cette commande, jamais en pipeline
 * courant.
 */
final readonly class AnthropicCompanySectorClient implements CompanySectorClient
{
    private const ENDPOINT = 'v1/messages';

    public function __construct(
        private HttpClientInterface $anthropicClient,
        private string $model,
        private int $maxTokens = 8192,
    ) {
    }

    public function classify(array $companies): array
    {
        if ([] === $companies) {
            return [];
        }

        try {
            $data = $this->anthropicClient->request('POST', self::ENDPOINT, [
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->userPrompt($companies)],
                    ],
                    'output_config' => ['format' => $this->schema()],
                ],
                'timeout' => 180,
            ])->toArray();
        } catch (HttpExceptionInterface $e) {
            throw new VocabularyEnrichException('Appel Anthropic échoué : '.$e->getMessage(), previous: $e);
        }

        if ('refusal' === ($data['stop_reason'] ?? null)) {
            throw new VocabularyEnrichException('Le modèle a refusé la requête de classification.');
        }

        return $this->parseDecisions($data);
    }

    private function systemPrompt(): string
    {
        $sectors = implode(', ', array_map(static fn (Sector $s): string => $s->value, Sector::cases()));

        return <<<PROMPT
            Tu classes des entreprises dans UN secteur d'activité, d'après leur nom, en t'appuyant sur ta
            connaissance des sociétés (ex. Deloitte → CONSEIL_AUDIT, OCP Group → INDUSTRIE, BNP Paribas →
            BANQUE_FINANCE, Airbus / Safran → AERONAUTIQUE, Orange → TELECOM, EDF → ENERGIE).
            Secteurs autorisés (valeurs EXACTES) : {$sectors}.
            Règles : freelance / indépendant / auto-entrepreneur → INDEPENDANT ; nom illisible, vide,
            générique (« - », « stage ») ou entreprise inconnue non rattachable → AUTRE. Réponds
            uniquement via le schéma imposé, une décision par entreprise fournie, en reprenant sa `key`.
            PROMPT;
    }

    /**
     * @param list<CompanyToClassify> $companies
     */
    private function userPrompt(array $companies): string
    {
        $lines = implode("\n", array_map(
            static fn (CompanyToClassify $c): string => \sprintf('- key=%s | %s (fréquence %d)', $c->key, $c->name, $c->frequency),
            $companies,
        ));

        return "Entreprises à classer :\n".$lines;
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
                            'required' => ['key', 'sector'],
                            'properties' => [
                                'key' => ['type' => 'string'],
                                'sector' => ['type' => 'string', 'enum' => array_map(static fn (Sector $s): string => $s->value, Sector::cases())],
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
     * @return list<CompanySectorDecision>
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
            $sector = Sector::tryFrom((string) ($raw['sector'] ?? '')) ?? Sector::AUTRE;
            $decisions[] = new CompanySectorDecision((string) ($raw['key'] ?? ''), $sector);
        }

        return $decisions;
    }
}
