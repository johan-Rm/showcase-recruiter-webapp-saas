<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\RevealType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload de dévoilement : le candidat, et CE QU'ON DÉVOILE de lui.
 *
 * Le type est obligatoire et sans défaut : dévoiler coûte un crédit, l'appelant doit dire lequel
 * des trois accès il paie plutôt que d'en hériter un par omission.
 */
final class RevealInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $candidateId = '';

    #[Assert\NotBlank(message: 'Précisez ce que vous dévoilez : coordonnées, CV ou compte rendu.')]
    #[Assert\Choice(callback: [RevealType::class, 'values'])]
    public string $type = '';
}
