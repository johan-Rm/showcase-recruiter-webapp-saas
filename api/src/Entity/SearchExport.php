<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal d'un export de profils (task 154, ex-task 077) : de la donnée personnelle dévoilée est
 * SORTIE de la plateforme. On trace qui, quoi, quand — c'est la contrepartie de l'export, et la
 * matière de l'activité d'équipe.
 *
 * `candidateUuids` fige les profils réellement sortis : la sélection, elle, ne survit pas à l'écran.
 * L'accusé RGPD est horodaté par le SERVEUR — une date fournie par le client ne prouverait rien.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_export')]
class SearchExport extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: SavedSearch::class)]
        #[ORM\JoinColumn(name: 'search_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public SavedSearch $search,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'exported_by_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $exportedBy,
        #[ORM\Column(type: 'integer')]
        public int $candidateCount,
        /** @var list<string> uuids des candidats sortis */
        #[ORM\Column(type: 'json')]
        public array $candidateUuids,
        #[ORM\Column(type: 'datetime_immutable')]
        public DateTimeImmutable $gdprAcknowledgedAt = new DateTimeImmutable(),
    ) {
        parent::__construct();
    }
}
