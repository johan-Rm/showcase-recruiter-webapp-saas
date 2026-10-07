<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'une recherche. Champs nullable pour permettre le PATCH partiel.
 *
 * `criteria` est une liste ORDONNÉE — l'ordre de sélection est signifiant, il produit le nom auto
 * côté front. L'API le préserve, elle ne le trie pas.
 *
 * Un `name` vide réactive le nom auto ; un nom renseigné le désactive (task 072).
 */
final class SavedSearchInput
{
    #[Assert\Length(max: 120)]
    public ?string $name = null;

    /**
     * UNE RECHERCHE SANS CRITÈRE N'EN EST PAS UNE : sans filtre, son jeu de résultats est le vivier
     * entier, et rien à l'écran ne distingue une recherche qui n'a jamais rien demandé d'une
     * recherche ordinaire. L'écran refuse déjà — son bouton reste éteint —, mais un garde-fou qui ne
     * vit que dans le bouton est un garde-fou qu'un appel direct contourne.
     *
     * `null` PASSE, et c'est voulu : sur une modification, ne pas parler des critères signifie
     * « n'y touche pas ». Seule une liste explicitement vide est refusée, ce qu'une contrainte de
     * nombre sur un champ facultatif fait exactement.
     *
     * @var list<array{facet: string, value: string, group: string}>|null
     */
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un critère à votre recherche.')]
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'facet' => [new Assert\NotBlank(), new Assert\Length(max: 60)],
                'value' => [new Assert\Type('string'), new Assert\Length(max: 255)],
                'group' => [new Assert\Type('string'), new Assert\Length(max: 60)],
            ],
            allowMissingFields: false,
        ),
    ])]
    public ?array $criteria = null;

    #[Assert\Type('bool')]
    public ?bool $isShared = null;

    /**
     * Déclarer le travail terminé, ou le rouvrir.
     *
     * Une recherche close n'accepte plus AUCUNE autre modification : le processeur refuse
     * en 409 tant qu'elle n'est pas rouverte. Sans ça, « close » n'engagerait rien.
     */
    #[Assert\Type('bool')]
    public ?bool $isClosed = null;
}
