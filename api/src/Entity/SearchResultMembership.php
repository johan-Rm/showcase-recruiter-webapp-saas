<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Appartenance matérialisée d'un candidat au jeu de résultats d'une recherche, recalculée à
 * l'ingestion. Cf. task 072.
 *
 * Elle existe parce que « a rejoint le jeu de résultats » ne se déduit PAS d'un `createdAt` : un
 * candidat présent depuis six mois qui déclare un séjour au Maroc rejoint la recherche
 * « présent < 30 j » sans être un nouveau profil — c'est le cas d'usage central de showcase-recruiter-webapp-saas.
 * Compter les seules entrées du vivier le raterait.
 *
 * `firstMatchedAt` date l'entrée dans CE jeu de résultats ; le badge `+N` compte les entrées
 * postérieures au repère de lecture du recruteur (SearchView). Un candidat qui sort du jeu perd sa
 * ligne : s'il y revient, il compte de nouveau comme une entrée.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_result_membership')]
#[ORM\UniqueConstraint(name: 'uniq_membership_search_candidate', columns: ['search_id', 'candidate_id'])]
#[ORM\Index(name: 'idx_membership_search_first_matched', columns: ['search_id', 'first_matched_at'])]
class SearchResultMembership extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: SavedSearch::class)]
        #[ORM\JoinColumn(name: 'search_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public SavedSearch $search,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        #[ORM\Column(type: 'datetime_immutable')]
        public DateTimeImmutable $firstMatchedAt = new DateTimeImmutable(),
        /**
         * Combien des critères de la recherche ce profil satisfait — le rang du jeu.
         *
         * MATÉRIALISÉ ici et pas calculé à la lecture : le jeu se trie en SQL, sur des lignes, alors
         * que la correspondance se lit sur des entités chargées. Un tri par un score que seul PHP
         * sait produire obligerait à charger tout le jeu pour ordonner une page — et la liste
         * complète d'identifiants n'aurait plus le même ordre que les cartes.
         *
         * Recalculé avec l'appartenance, donc à chaque écriture de la recherche et à chaque
         * ingestion : c'est le même moment, et les critères ne changent pas entre les deux.
         */
        #[ORM\Column(type: 'smallint', options: ['default' => 0])]
        public int $matchedCount = 0,
    ) {
        parent::__construct();
    }
}
