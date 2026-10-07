<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'acceptation d'une invitation (parcours public, identifié par le token d'URL) :
 * le collègue choisit son mot de passe et renseigne son identité.
 */
final class AcceptInvitationInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $givenName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $familyName = '';
}
