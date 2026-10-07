<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Un accès communiqué dans un e-mail : où aller, et avec quoi entrer.
 *
 * RÉSERVÉ AUX ENVIRONNEMENTS DE RECETTE. Un mot de passe qui voyage par e-mail se transfère,
 * s'archive et se lit sur un téléphone perdu — il ne quitte cet objet que parce que la
 * préproduction n'héberge que des données de démonstration. Ce n'est pas ce gabarit qui l'empêche
 * de partir ailleurs : le garde-fou vit chez l'appelant, seul à savoir sur quel environnement il
 * tourne (cf. `OPS-035`). Ici, aucun accès fourni signifie aucun accès rendu, et c'est tout ce que
 * cette classe promet.
 */
final readonly class EmailAccess
{
    public function __construct(
        /** Ce que la personne reconnaîtra : « Espace entreprise », « Administration ». */
        public string $label,
        public string $url,
        public string $login,
        public string $password,
    ) {
    }
}
