<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * POURQUOI CE PROFIL répond à cette recherche — une entrée par profil expliqué.
 *
 * L'explication appartient au couple recherche-profil, jamais au profil seul : le même candidat
 * répond à deux recherches pour deux motifs, et une seule des deux versions survivrait s'il la
 * portait. C'est aussi pourquoi elle voyage à part — 601 octets par couple, contre 39 pour un
 * identifiant.
 *
 * `id` est celui du profil, de quoi recoller l'explication à la fiche que l'appelant tient déjà.
 */
final class SearchExplanationOutput
{
    /** @param list<CriterionMatchOutput> $matches les critères de la recherche, vus depuis ce profil */
    public function __construct(
        public string $id,
        public array $matches,
    ) {
    }
}
