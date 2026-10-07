<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Sector;
use App\Enum\VocabularyStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * Secteur d'activité d'une entreprise du parcours, déduit de son nom (le secteur n'est fourni par
 * aucune source — cf. task 105). Chargé depuis `sectors.json` par `app:company:sectors:import`.
 *
 * `companyKey` est la clé STABLE du harvester : `linkedinCompanyId` quand il existe (dédoublonne les
 * graphies d'un même employeur), sinon `name:<nom normalisé>`. C'est elle qui fait le lien avec les
 * postes de `candidate_jarvi`, et elle qui évite de reclasser une entreprise déjà connue.
 *
 * `status` reste CANDIDAT tant qu'un humain n'a pas relu : bon pour élargir une recherche, pas pour
 * un filtre dur.
 */
#[ORM\Entity]
#[ORM\Table(name: 'company_sector')]
#[ORM\UniqueConstraint(name: 'uniq_company_sector_key', columns: ['company_key'])]
#[ORM\Index(name: 'idx_company_sector_sector', columns: ['sector'])]
class CompanySector extends AbstractEntity
{
    public function __construct(
        #[ORM\Column(length: 190)]
        public string $companyKey,
        #[ORM\Column(length: 190)]
        public string $name,
        #[ORM\Column(enumType: Sector::class)]
        public Sector $sector,
        #[ORM\Column(type: 'integer')]
        public int $frequency = 0,
        #[ORM\Column(enumType: VocabularyStatus::class)]
        public VocabularyStatus $status = VocabularyStatus::CANDIDAT,
    ) {
        parent::__construct();
    }
}
