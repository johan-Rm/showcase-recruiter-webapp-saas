<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\MagicLinkAudience;
use Symfony\Component\Validator\Constraints as Assert;

final class MagicLinkRequestInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    /**
     * Espace d'où part la demande : décide du sous-domaine vers lequel le lien renvoie (une build par
     * espace). Liste fermée — l'URL est résolue côté serveur. Absent : déduit du compte s'il existe.
     */
    #[Assert\Choice(callback: [self::class, 'audiences'], message: 'Espace inconnu.')]
    public ?string $space = null;

    /** @return list<string> */
    public static function audiences(): array
    {
        return array_map(static fn (MagicLinkAudience $audience): string => $audience->value, MagicLinkAudience::cases());
    }
}
