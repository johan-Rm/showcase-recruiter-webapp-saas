<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CandidateOptionStatus;
use App\Enum\CandidateOptionType;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Option payante candidat V2 (monétisation espace candidat). Brouillon technique conservé tant que
 * l'offre, le paiement et le consentement ne sont pas cadrés. Cf. data-model.md.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_option')]
class CandidateOption extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\Column(enumType: CandidateOptionType::class)]
        public CandidateOptionType $type,
        #[ORM\Column]
        public DateTimeImmutable $startsAt,
        #[ORM\Column]
        public DateTimeImmutable $endsAt,
        #[ORM\Column(enumType: CandidateOptionStatus::class)]
        public CandidateOptionStatus $status = CandidateOptionStatus::ACTIVE,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $stripePaymentId = null,
    ) {
        parent::__construct();
    }
}
