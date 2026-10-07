<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Enum\Sector;
use Doctrine\DBAL\Connection;

/**
 * Référentiel entreprise → secteur, chargé UNE fois puis gardé en mémoire : une ingestion parcourt
 * des milliers de postes, on ne requête pas la base à chaque poste. Le volume est petit (~1 200
 * lignes) et la table est en lecture seule pendant un run.
 */
final class CompanySectorMap
{
    /** @var array<string, Sector>|null */
    private ?array $map = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, Sector> clé stable d'entreprise → secteur */
    public function all(): array
    {
        if (null !== $this->map) {
            return $this->map;
        }

        $map = [];
        foreach ($this->connection->fetchAllAssociative('SELECT company_key, sector FROM company_sector') as $row) {
            $sector = Sector::tryFrom((string) $row['sector']);
            if ($sector instanceof Sector) {
                $map[(string) $row['company_key']] = $sector;
            }
        }

        return $this->map = $map;
    }
}
