<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Processor\LabelReviewProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Relecture d'un LIBELLÉ (task 124), un cran sous le concept (`120`). Sert à exclure « mot à mot »
 * un synonyme parasite de l'élargissement, sans toucher au concept qui, lui, reste bon.
 *
 * Les libellés n'ont pas de code stable : ils sont désignés par leur **uuid**.
 *
 * Deux statuts seulement : `DEPRECIE` (exclure — réversible, ignore temporairement) et `CANDIDAT`
 * (restaurer). `VALIDE` est refusé : un libellé ne se valide pas seul, c'est le CONCEPT qui porte la
 * validation.
 */
#[ApiResource(
    shortName: 'LabelReview',
    operations: [
        new Post(
            uriTemplate: '/admin/referentials/labels/reviews',
            name: 'admin_label_review',
            processor: LabelReviewProcessor::class,
        ),
    ],
)]
final class LabelReviewInput
{
    /** @var list<string> */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1, max: 200)]
    public array $labelIds = [];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DEPRECIE', 'CANDIDAT'])]
    public string $status = 'DEPRECIE';
}
