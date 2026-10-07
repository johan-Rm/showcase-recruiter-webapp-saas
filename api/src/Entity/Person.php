<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\OriginLocale;
use Doctrine\ORM\Mapping as ORM;

/**
 * 🌐 schema.org Person. Identité partagée par tous (candidats, recruteurs, admins),
 * source de vérité showcase-recruiter-webapp-saas, poussée vers Jarvi pour les candidats (externalId = uuid).
 * Un Person peut exister sans User (invité non inscrit). Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'person')]
class Person extends AbstractEntity
{
    public function __construct(
        #[ORM\Column(length: 100)]
        public string $givenName,
        #[ORM\Column(length: 100)]
        public string $familyName,
        #[ORM\Column(length: 180, nullable: true)]
        public ?string $contactEmail = null,
        #[ORM\Column(length: 30, nullable: true)]
        public ?string $phone = null,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $linkedinUrl = null,
        #[ORM\Column(length: 2, nullable: true)]
        public ?string $country = null,
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $city = null,
        #[ORM\Column(enumType: OriginLocale::class)]
        public OriginLocale $originLocale = OriginLocale::FR,
    ) {
        parent::__construct();
    }
}
