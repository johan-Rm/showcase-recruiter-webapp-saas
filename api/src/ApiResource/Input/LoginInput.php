<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload documentaire de login. L'authentification réelle est interceptée par `json_login`,
 * mais ce DTO permet d'exposer les règles au webapp via /v1/validation-rules/authLogin.
 */
final class LoginInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $password = '';
}
