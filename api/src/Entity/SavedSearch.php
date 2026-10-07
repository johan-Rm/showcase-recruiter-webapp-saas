<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recherche d'un recruteur — objet central du cockpit entreprise : elle porte ses critères, ses
 * favoris et ses crédits consommés. Cf. data-model.md § SavedSearch.
 *
 * `criteria` = liste ORDONNÉE de `{facet, value, group}` (valeurs métier, pas d'ids/FK). L'ordre
 * est signifiant : il produit le nom auto côté front, que l'API se contente de stocker.
 *
 * Partage : `isShared` ouvre la LECTURE aux membres de l'organisation. L'écriture reste à l'auteur
 * (un seul auteur, aucune co-édition — cf. SavedSearchVoter).
 */
#[ORM\Entity]
#[ORM\Table(name: 'saved_search')]
class SavedSearch extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Recruiter::class)]
        #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
        public Recruiter $owner,
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
        public Organization $organization,
        #[ORM\Column(length: 255)]
        public string $name,
        /** @var list<array{facet: string, value: string, group: string}> critères ordonnés */
        #[ORM\Column(type: 'json')]
        public array $criteria = [],
        /** Renommer manuellement désactive le nom auto ; vider le nom le réactive. */
        #[ORM\Column(type: 'boolean')]
        public bool $isAutoNamed = true,
        #[ORM\Column(type: 'boolean')]
        public bool $isShared = false,
        #[ORM\Column(type: 'boolean')]
        public bool $alertEnabled = false,
        /** @var list<string> canaux IN_APP / EMAIL (V2) */
        #[ORM\Column(type: 'json')]
        public array $channels = [],
    ) {
        parent::__construct();

        $this->lastActivityAt = new DateTimeImmutable();
    }

    /**
     * LE DERNIER CONTACT HUMAIN avec cette recherche — ouvrir son jeu, trancher un profil, ajuster
     * ses critères. C'est lui qui décide de sa clôture automatique après une longue inaptitude.
     *
     * **Et surtout pas `updatedAt`.** Le jeu de résultats se recalcule à chaque ingestion Jarvi, sur
     * TOUTES les recherches : `updatedAt` y bougerait sans que personne n'ait rien fait, et aucune
     * recherche ne se fermerait jamais. Les deux dates répondent à deux questions différentes —
     * « quand la ligne a-t-elle changé » et « quand quelqu'un s'en est-il occupé ».
     *
     * Portée par la RECHERCHE et non par son lecteur : sur une recherche partagée, le geste d'un
     * collègue la garde ouverte pour tout le monde, comme `closedAt` vaut pour tout le monde.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $lastActivityAt;

    /** Quelqu'un vient de s'occuper de cette recherche : le compte à rebours repart. */
    public function touch(?DateTimeImmutable $at = null): void
    {
        $this->lastActivityAt = $at ?? new DateTimeImmutable();
    }

    /**
     * Quand son auteur a déclaré le travail terminé — nulle tant que la recherche est active
     * (task API-028).
     *
     * Hors du constructeur : une recherche ne naît jamais close, et l'y faire figurer inviterait à
     * la créer dans cet état.
     *
     * Aucune colonne « close par » ne l'accompagne : `SavedSearchVoter` réserve l'écriture à
     * l'auteur, elle ne pourrait donc jamais contenir autre chose que `owner`.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $closedAt = null;
}
