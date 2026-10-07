<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Favori d'une RECHERCHE — le filet large de la Table (task 073, refonte de la 008).
 *
 * Favoriser fonctionne sur un profil MASQUÉ : c'est un marque-page de tri, il ne coûte rien et
 * n'engage rien : on retient AVANT de payer. C'est la seule liste de rétention du produit.
 *
 * Portée : la recherche, pas l'organisation. Deux recherches d'un même recruteur ont des favoris
 * distincts. Seuls le dévoilement et le solde de crédits restent communs à l'entreprise.
 */
#[ORM\Entity]
#[ORM\Table(name: 'favorite')]
#[ORM\UniqueConstraint(name: 'uniq_favorite_search_candidate', columns: ['search_id', 'candidate_id'])]
class Favorite extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: SavedSearch::class)]
        #[ORM\JoinColumn(name: 'search_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public SavedSearch $search,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false)]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'added_by_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $addedBy,
    ) {
        parent::__construct();
    }
}
