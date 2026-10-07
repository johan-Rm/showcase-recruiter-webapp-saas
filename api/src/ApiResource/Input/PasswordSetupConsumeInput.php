<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class PasswordSetupConsumeInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $token = '';

    /**
     * Mot de passe SOLIDE, exigé côté serveur : le front guide avec une checklist, mais la porte se
     * ferme ici — un appel direct ne doit pas pouvoir poser un mot de passe faible. Règles explicites
     * (et non un score d'entropie) : chaque violation dit LAQUELLE corriger, et la checklist front les
     * reflète une à une. `\p{Lu}`/`\p{Ll}` couvrent les majuscules/minuscules accentuées, pas juste a-z.
     */
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 255, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/\p{Lu}/u', message: 'Le mot de passe doit contenir au moins une majuscule.')]
    #[Assert\Regex(pattern: '/\p{Ll}/u', message: 'Le mot de passe doit contenir au moins une minuscule.')]
    #[Assert\Regex(pattern: '/\d/', message: 'Le mot de passe doit contenir au moins un chiffre.')]
    public string $password = '';
}
