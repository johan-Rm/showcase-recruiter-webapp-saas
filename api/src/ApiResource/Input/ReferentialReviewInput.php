<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Processor\ReferentialReviewProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Relecture humaine d'un concept (tâche 120) : promotion en `VALIDE` ou mise au rebut en
 * `DEPRECIE`, avec traçabilité.
 *
 * Le concept est désigné par son **code stable** (`competence-001`), jamais par son libellé : un
 * renommage ne doit pas faire porter la décision sur un autre objet (cf. `113`).
 *
 * `CANDIDAT` n'est pas une valeur acceptée en entrée : c'est l'état par défaut de ce que la machine
 * produit, pas une décision qu'un humain prend. Annuler une validation se fait en dépréciant.
 */
#[ApiResource(
    shortName: 'ReferentialReview',
    operations: [
        new Post(
            uriTemplate: '/admin/referentials/reviews',
            name: 'admin_referential_review',
            processor: ReferentialReviewProcessor::class,
        ),
    ],
)]
final class ReferentialReviewInput
{
    /** @var list<string> */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1, max: 200)]
    public array $codes = [];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['VALIDE', 'DEPRECIE'])]
    public string $status = 'VALIDE';
}
