<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'ajout d'un candidat aux favoris d'une recherche.
 */
final class FavoriteInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $candidateId = '';
}
