<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Entity\CompanySector;
use App\Enum\Sector;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Charge `sectors.json` (produit hors ligne) dans la table `company_sector`.
 * Upsert idempotent sur `companyKey` : rejouer l'import ne duplique rien et rafraîchit nom,
 * secteur et fréquence. Aucun appel LLM ici — l'import est déterministe (cf. task 107).
 */
final readonly class CompanySectorImporter
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param array<string, mixed> $sectors contenu décodé de sectors.json
     */
    public function import(array $sectors, bool $dryRun = false): CompanySectorImportReport
    {
        $report = new CompanySectorImportReport();
        $companies = $sectors['companies'] ?? [];
        if (!\is_array($companies)) {
            return $report;
        }

        $repository = $this->em->getRepository(CompanySector::class);

        foreach ($companies as $key => $data) {
            $key = (string) $key;
            if ('' === $key || !\is_array($data)) {
                ++$report->skipped;
                continue;
            }

            $sector = Sector::tryFrom((string) ($data['sector'] ?? ''));
            if (!$sector instanceof Sector) {
                ++$report->skipped;   // secteur hors nomenclature : on n'invente pas
                continue;
            }

            $name = (string) ($data['name'] ?? '');
            $frequency = (int) ($data['frequence'] ?? 0);

            $existing = $repository->findOneBy(['companyKey' => $key]);
            if ($existing instanceof CompanySector) {
                $existing->name = $name;
                $existing->sector = $sector;
                $existing->frequency = $frequency;
                ++$report->updated;
                continue;
            }

            $this->em->persist(new CompanySector($key, $name, $sector, $frequency));
            ++$report->created;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return $report;
    }
}
