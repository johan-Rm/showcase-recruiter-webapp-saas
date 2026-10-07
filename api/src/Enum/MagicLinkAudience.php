<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Espace vers lequel un magic link renvoie. Chaque espace est servi par son propre host (une build
 * par espace) : un lien candidat n'a rien à faire sur le sous-domaine entreprise, et inversement.
 *
 * Volontairement une liste fermée, mappée côté serveur vers une URL **configurée** : accepter une
 * URL de retour venue du client ouvrirait une redirection arbitraire depuis un e-mail signé showcase-recruiter-webapp-saas.
 */
enum MagicLinkAudience: string
{
    case CANDIDAT = 'candidat';
    case ENTREPRISE = 'entreprise';
}
