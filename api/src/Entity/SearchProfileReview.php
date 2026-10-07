<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Où EN EST UN RECRUTEUR sur un profil d'une recherche : vu, écarté (task API-007).
 *
 * Parcourir une recherche est un tri qui ne tient pas en une séance — plusieurs centaines de profils
 * défilent un par un. Sans trace, celui qui revient le lendemain recommence à l'aveugle.
 *
 * PERSONNEL, contrairement au favori, et ce n'est pas un détail de modèle. Favoriser est ADDITIF :
 * un favori partagé ajoute de l'attention à un collègue, il ne lui retire rien. Écarter est
 * SOUSTRACTIF : partagé, il ferait disparaître de la pile d'un collègue un profil qu'il n'a jamais
 * choisi d'ignorer — et il ne saurait même pas ce qu'il n'a pas vu. Rejeter est en outre un jugement
 * relatif à un besoin : « trop junior pour moi » est peut-être exactement ce que cherche l'autre.
 *
 * Un fait tranche seul : sur une recherche partagée, le destinataire ne peut même pas favoriser
 * (elle est en lecture seule pour lui). Un état personnel n'écrit pas sur la recherche de l'autre,
 * il écrit sur MON parcours de cette recherche — comme le fait déjà le repère de nouveautés.
 *
 * L'information circule quand même, sans que l'effet s'impose : l'écran peut annoncer qu'un collègue
 * a écarté ce profil, tout en le gardant dans ma pile.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_profile_review')]
#[ORM\UniqueConstraint(name: 'uniq_review_search_candidate_recruiter', columns: ['search_id', 'candidate_id', 'recruiter_id'])]
#[ORM\Index(name: 'idx_review_search_recruiter', columns: ['search_id', 'recruiter_id'])]
class SearchProfileReview extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: SavedSearch::class)]
        #[ORM\JoinColumn(name: 'search_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public SavedSearch $search,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Recruiter $recruiter,
        /**
         * Quand le profil a été REGARDÉ — pas simplement traversé.
         *
         * Le seuil vit à l'écran (une carte restée affichée un instant) et pas ici : dans une pile
         * qui défile, tout ce qu'on traverse est techniquement vu, et un défilement rapide marquerait
         * le jeu entier en quelques secondes. L'API enregistre le verdict, elle ne l'invente pas.
         */
        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        public ?DateTimeImmutable $seenAt = null,
        /** Écarté de la pile — réversible : c'est une date qu'on efface, pas une ligne qu'on perd. */
        #[ORM\Column(type: 'datetime_immutable', nullable: true)]
        public ?DateTimeImmutable $dismissedAt = null,
    ) {
        parent::__construct();
    }
}
