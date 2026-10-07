<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\MagicLinkAudience;
use App\Enum\SocialProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrée du social login : un `id_token` OIDC obtenu par le BFF auprès du fournisseur, présenté à
 * l'API pour vérification. L'API n'ouvre PAS de session — elle renvoie un magic link à l'e-mail
 * vérifié (cf. SocialMagicLinkProcessor). Le secret OAuth reste côté BFF ; ici ne transite que la
 * preuve signée.
 */
final class SocialMagicLinkInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [self::class, 'providers'], message: 'Fournisseur inconnu.')]
    public string $provider = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 8192)]
    public string $idToken = '';

    /**
     * Espace d'où part la demande : décide du sous-domaine vers lequel le magic link renvoie. Même
     * liste fermée que la demande de lien classique (cf. MagicLinkRequestInput).
     */
    #[Assert\Choice(callback: [self::class, 'audiences'], message: 'Espace inconnu.')]
    public ?string $space = null;

    /** @return list<string> */
    public static function providers(): array
    {
        return array_map(static fn (SocialProvider $provider): string => $provider->value, SocialProvider::cases());
    }

    /** @return list<string> */
    public static function audiences(): array
    {
        return array_map(static fn (MagicLinkAudience $audience): string => $audience->value, MagicLinkAudience::cases());
    }
}
