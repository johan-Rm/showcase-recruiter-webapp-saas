<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use Doctrine\DBAL\Connection;

/**
 * Récolte les entreprises DISTINCTES du parcours depuis la réplica `candidate_jarvi` (LECTURE
 * SEULE), avec une clé stable et leur fréquence (nombre de postes).
 *
 * Clé = `linkedinCompanyId` quand il existe (dédoublonne les graphies), sinon `name:<nom normalisé>`.
 * SQL natif → porte sa propre clause `deleted_at IS NULL` (le soft delete Doctrine ne couvre pas le
 * SQL natif). Sortie destinée à la classification LLM hors ligne — jamais dans une boucle courante.
 */
final readonly class CompanySectorHarvester
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return list<CompanyToClassify> */
    public function harvest(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT
                COALESCE(NULLIF(btrim(we->>'linkedinCompanyId'), ''), 'name:' || lower(btrim(we->>'companyName'))) AS company_key,
                max(COALESCE(NULLIF(btrim((we->'linkedinCompanyData')->>'name'), ''), btrim(we->>'companyName'))) AS name,
                count(*) AS freq
            FROM candidate_jarvi cj, json_array_elements(cj.work_experiences) AS we
            WHERE cj.deleted_at IS NULL AND btrim(coalesce(we->>'companyName', '')) <> ''
            GROUP BY 1
            ORDER BY freq DESC
            SQL);

        $companies = [];
        foreach ($rows as $row) {
            $companies[] = new CompanyToClassify(
                (string) $row['company_key'],
                (string) $row['name'],
                (int) $row['freq'],
            );
        }

        return $companies;
    }
}
