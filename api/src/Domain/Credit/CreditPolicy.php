<?php

declare(strict_types=1);

namespace App\Domain\Credit;

/**
 * Règles métier de la facturation en crédits (V1). Source unique des constantes crédits
 * (cf. data-model.md) — réutilisée par le Domain et exposée via /v1/config.
 */
final class CreditPolicy
{
    /** Crédits offerts à la création d'une Organization. */
    public const int WELCOME_CREDITS = 2;

    /** Coût d'un dévoilé de profil. */
    public const int REVEAL_COST = 1;

    /**
     * Plafond d'un lot de dévoilements — refusé au-delà.
     *
     * C'est l'action la plus coûteuse du produit : sur une recherche large, un clic peut viser des
     * centaines de profils. Le plafond vit ICI et non dans l'UI — un garde-fou que le navigateur
     * applique n'en est pas un, n'importe quel appel direct le franchit.
     */
    public const int MAX_REVEAL_BATCH = 50;
}
