<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce qu'une proposition d'auto-complétion promet à la recherche (task API-037).
 *
 * La distinction n'est pas documentaire : les deux ne se cherchent pas pareil, et le recruteur doit
 * pouvoir le savoir AVANT de choisir. Sans elle, un intitulé brut se lit comme un métier du
 * référentiel, et l'absence de synonymes passe pour un défaut du moteur.
 *
 * Nommée par ce qu'elle promet et non par la table d'où elle sort : c'est la promesse qui intéresse
 * celui qui choisit, et c'est elle qui reste vraie si la source change.
 */
enum SuggestionKind: string
{
    /** Un métier du dictionnaire : la recherche élargira aux synonymes et aux traductions. */
    case CONCEPT = 'CONCEPT';

    /**
     * Un terme porté tel quel par les profils — intitulé de poste écrit, ou compétence. La recherche
     * trouvera ce texte, littéralement, et rien d'autre.
     */
    case LITERAL = 'LITERAL';
}
