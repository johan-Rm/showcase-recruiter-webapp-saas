<?php

declare(strict_types=1);

namespace App\Domain\Search\Demo;

/**
 * Où en est une recherche de démonstration — task API-029. Les trois nombres que son rail annonce
 * sous le volume : les nouveautés, les profils vus, les profils retenus.
 *
 * Ce sont des INTENTIONS, exprimées avant de connaître la taille du jeu ; `sharesFor()` les ramène à
 * ce que le jeu permet réellement.
 */
final readonly class DemoSearchProgress
{
    public function __construct(
        /** Part du jeu déjà parcourue, en pour cent. */
        public int $seenPercent,
        public int $retained,
        /** Entrées ramenées APRÈS le repère de lecture : le badge « +N ». */
        public int $fresh,
    ) {
    }

    /**
     * Combien de profils, sur un jeu de cette taille, seront nouveaux, puis vus, puis retenus.
     *
     * L'ordre des bornes porte la vraisemblance de la carte, et chacune corrige une carte qu'on ne
     * croirait pas :
     *
     * - les arrivants récents sont retirés du jeu AVANT de calculer la part parcourue — sinon une
     *   recherche « entièrement vue » compterait comme vus des profils arrivés depuis son dernier
     *   passage, alors qu'ils sont précisément ce qui reste à voir ;
     * - les retenus ne dépassent jamais les vus — on ne met pas de côté un profil qu'on n'a pas
     *   ouvert.
     *
     * @return array{fresh: int, seen: int, retained: int}
     */
    public function sharesFor(int $total): array
    {
        $fresh = max(0, min($this->fresh, $total));
        $seen = (int) round(($total - $fresh) * $this->seenPercent / 100);

        return [
            'fresh' => $fresh,
            'seen' => $seen,
            'retained' => min($this->retained, $seen),
        ];
    }
}
