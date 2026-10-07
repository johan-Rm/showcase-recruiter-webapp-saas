<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Port de regroupement sémantique des écoles. L'implémentation par défaut interroge un LLM hors
 * ligne ; les tests fournissent un double déterministe. Le pipeline courant n'en dépend jamais —
 * et cette chaîne est de surcroît désactivée par défaut (cf. la commande app:school:group).
 */
interface SchoolGroupingClient
{
    /**
     * @param list<HarvestedSchool> $schools
     *
     * @return list<SchoolGroupingDecision> une décision par établissement fourni
     */
    public function group(array $schools): array;
}
