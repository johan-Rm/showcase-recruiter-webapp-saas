<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * D'où l'on regarde le jeu de résultats d'une recherche.
 *
 * Les trois valeurs nomment un POINT DE VUE, pas un filtre : elles ne se combinent pas, et l'API n'en
 * accepte qu'une. « Retenu et écarté » n'a pas de sens à servir — c'est justement ce que le client
 * s'interdit de produire.
 *
 * Une valeur inconnue retombe sur la pile de travail plutôt que d'échouer : le périmètre est de la
 * donnée d'URL, et une adresse partagée ne doit pas casser après une évolution du moteur.
 */
enum SearchScope: string
{
    /** La pile de travail : tout le jeu, moins ce que le recruteur courant a écarté. */
    case PILE = 'pile';

    /** La corbeille : seulement les écartés, d'où l'on peut remettre. */
    case DISMISSED = 'dismissed';

    /**
     * Ce qui a été retenu — écartés déduits.
     *
     * Les retirer n'est pas un détail : « Favoris » doit rester un SOUS-ENSEMBLE de la pile, sinon
     * les deux onglets de « Ma sélection » annoncent des comptes qui ne se recouvrent pas. C'est
     * exactement le défaut que le client a dû corriger de son côté.
     */
    case FAVORITES = 'favorites';

    public static function fromRequest(?string $value): self
    {
        return self::tryFrom($value ?? '') ?? self::PILE;
    }
}
