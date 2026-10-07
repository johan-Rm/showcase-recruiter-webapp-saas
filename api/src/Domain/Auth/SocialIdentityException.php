<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use RuntimeException;

/**
 * L'`id_token` présenté n'a pas pu être prouvé : signature invalide, expiré, mauvais émetteur ou
 * audience, e-mail non vérifié, ou fournisseur non configuré. Le message reste volontairement
 * GÉNÉRIQUE — l'appelant n'a pas à savoir LEQUEL de ces contrôles a échoué (un attaquant non plus).
 * Le processor la traduit en 401 sans divulguer le détail.
 */
final class SocialIdentityException extends RuntimeException
{
}
