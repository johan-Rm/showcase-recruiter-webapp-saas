<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Port de classification entreprise → secteur. L'implémentation par défaut interroge un LLM hors
 * ligne ; les tests fournissent un double déterministe. Le pipeline courant n'en dépend jamais.
 */
interface CompanySectorClient
{
    /**
     * @param list<CompanyToClassify> $companies
     *
     * @return list<CompanySectorDecision> une décision par entreprise fournie
     */
    public function classify(array $companies): array;
}
