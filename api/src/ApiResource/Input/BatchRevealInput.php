<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\RevealType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'un dévoilement en LOT : les profils, et l'accès qu'on paie sur chacun.
 *
 * UN SEUL type par lot, jamais les trois : cocher vingt profils et voir partir soixante crédits
 * serait la surprise que ce produit doit éviter. Le recruteur choisit ce qu'il achète.
 *
 * Le plafond est aussi gardé côté domaine — celui d'ici ne sert qu'à rendre un 422 propre plutôt
 * qu'une exception.
 */
final class BatchRevealInput
{
    /**
     * @var list<string>
     */
    #[Assert\NotBlank(message: 'Sélectionnez au moins un profil à dévoiler.')]
    // 50 recopié et non importé : la couche ApiResource ne dépend pas du Domain (deptrac). La
    // VRAIE garde reste `CreditPolicy::MAX_REVEAL_BATCH`, que tout appelant traverse ; celle-ci ne
    // sert qu'à rendre un 422 lisible plutôt qu'une exception.
    #[Assert\Count(max: 50, maxMessage: 'Un lot ne peut pas dépasser {{ limit }} profils.')]
    #[Assert\All([new Assert\Uuid()])]
    public array $candidateIds = [];

    #[Assert\NotBlank(message: 'Précisez ce que vous dévoilez : coordonnées, CV ou compte rendu.')]
    #[Assert\Choice(callback: [RevealType::class, 'values'])]
    public string $type = '';
}
