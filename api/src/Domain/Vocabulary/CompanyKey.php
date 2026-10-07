<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Clé STABLE d'une entreprise, unique point de jonction entre un poste du parcours et son secteur
 * ({@see \App\Entity\CompanySector}).
 *
 * `linkedinCompanyId` quand il existe — il dédoublonne les graphies d'un même employeur (« Catalyst
 * Business Solutions » vs « catalyst business solutions ») ; sinon repli sur le nom normalisé.
 *
 * ⚠️ {@see CompanySectorHarvester} calcule la MÊME clé en SQL : toute évolution ici doit être
 * répercutée là-bas, sinon les secteurs ne se rattachent plus aux postes.
 */
final class CompanyKey
{
    /**
     * @param array<string, mixed> $position poste Jarvi (companyName, linkedinCompanyId…)
     *
     * @return string|null null si le poste ne porte aucun employeur exploitable
     */
    public static function fromPosition(array $position): ?string
    {
        $linkedinId = trim((string) ($position['linkedinCompanyId'] ?? ''));
        if ('' !== $linkedinId) {
            return $linkedinId;
        }

        $name = trim((string) ($position['companyName'] ?? ''));

        return '' !== $name ? 'name:'.mb_strtolower($name) : null;
    }
}
