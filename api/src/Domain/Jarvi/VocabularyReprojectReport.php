<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Compte rendu d'une reprojection du dictionnaire sur le vivier (task API-035).
 *
 * `moved` compte les lignes de projection posées ou retirées — métiers reconnus et intitulés bruts
 * confondus —, `retitled` les postes actuels rerattachés. Les deux valent zéro quand le dictionnaire
 * n'a rien changé, ce qui est le cas de l'immense majorité des profils à chaque passage, et c'est
 * bien ce qu'on veut lire.
 */
final class VocabularyReprojectReport
{
    public int $moved = 0;

    public int $retitled = 0;

    public function __construct(public readonly int $profiles)
    {
    }
}
