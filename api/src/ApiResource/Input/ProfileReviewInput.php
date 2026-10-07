<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\ProfileReviewAction;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ce qu'un recruteur déclare sur des profils d'une recherche : vus, écartés, remis.
 *
 * EN LOT par construction, et ce n'est pas une commodité : marquer un profil vu à chaque carte
 * franchie ferait un appel par profil — plusieurs centaines pour un passage complet. Le client
 * accumule et pousse par paquets.
 *
 * Pas de plafond de lot ici, contrairement au dévoilement : cette action ne coûte rien et n'engage
 * rien. Le seul garde-fou utile est que les profils appartiennent bien à la recherche.
 */
final class ProfileReviewInput
{
    /** @var list<string> */
    #[Assert\NotBlank(message: 'Indiquez au moins un profil.')]
    #[Assert\All([new Assert\Uuid()])]
    public array $candidateIds = [];

    #[Assert\NotBlank(message: 'Précisez ce que vous déclarez : vu, écarté ou remis.')]
    #[Assert\Choice(callback: [ProfileReviewAction::class, 'values'])]
    public string $action = '';
}
