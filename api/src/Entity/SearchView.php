<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Repère de lecture d'une recherche, PAR RECRUTEUR : deux membres consultant la même recherche
 * partagée ont des badges `+N` indépendants. Cf. task 072.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_view')]
#[ORM\UniqueConstraint(name: 'uniq_search_view_search_recruiter', columns: ['search_id', 'recruiter_id'])]
class SearchView extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: SavedSearch::class)]
        #[ORM\JoinColumn(name: 'search_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public SavedSearch $search,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Recruiter $recruiter,
        #[ORM\Column(type: 'datetime_immutable')]
        public DateTimeImmutable $lastViewedAt = new DateTimeImmutable(),
    ) {
        parent::__construct();
    }
}
