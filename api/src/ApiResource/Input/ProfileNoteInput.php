<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'ajout d'une note collaborative sur un candidat (au sein d'une entreprise).
 */
final class ProfileNoteInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    public string $body = '';
}
