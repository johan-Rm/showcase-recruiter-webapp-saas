<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Fournisseur d'identité sociale accepté à l'entrée du tunnel. Liste FERMÉE : le social login ne sert
 * qu'à récupérer un e-mail vérifié, jamais à ouvrir une session (cf. SocialIdentityVerifier). Un
 * fournisseur hors de cette liste est rejeté avant tout appel réseau.
 */
enum SocialProvider: string
{
    case GOOGLE = 'google';
    case LINKEDIN = 'linkedin';
}
