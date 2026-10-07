<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Source des libellés de postes pour {@see TitleConceptResolver}. L'interface existe pour que la
 * règle de reconnaissance — la partie qui mérite d'être testée — reste éprouvable sans base.
 */
interface JobConceptCatalog
{
    /** @return array<string, JobConceptEntry> libellé exact → entrée */
    public function byTerm(): array;

    /**
     * Entrées dont l'un des mots apparaît dans l'intitulé — le vivier des correspondances partielles.
     *
     * @param list<string> $words
     *
     * @return list<JobConceptEntry>
     */
    public function candidatesFor(array $words): array;
}
