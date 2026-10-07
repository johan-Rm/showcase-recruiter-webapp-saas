<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un intitulé de poste sur lequel un recruteur a lancé une recherche, et que le dictionnaire ne
 * connaît pas (task API-024).
 *
 * POURQUOI CETTE TABLE. Le filtre par métier exige une correspondance exacte : un terme hors
 * dictionnaire ramène zéro profil, quoi qu'il y ait dans le vivier. C'est le bon comportement — il a
 * été examiné puis confirmé dans `API-023` —, mais il laisse une question sans réponse : **on ne sait
 * pas ce que le dictionnaire ne connaît pas.** Il s'enrichit à l'intuition, alors que les recruteurs
 * tapent chaque jour ce qu'ils cherchent vraiment.
 *
 * AUCUNE DONNÉE PERSONNELLE. Ni recruteur, ni organisation : le nombre d'occurrences suffit à décider
 * quoi instruire en premier, et l'alternative aurait constitué un historique nominatif de ce que
 * chaque client cherche.
 *
 * `term` est la CLÉ, normalisée — « Développeur Fullstack » et « développeur fullstack » sont la même
 * demande. `label` garde la casse d'origine, parce qu'une liste de termes à instruire se lit mieux
 * telle qu'elle a été écrite.
 */
#[ORM\Entity]
#[ORM\Table(name: 'unresolved_job_term')]
class UnresolvedJobTerm extends AbstractEntity
{
    /** Au-delà, ce n'est plus un intitulé de poste : un champ de recherche accepte n'importe quoi. */
    public const int MAX_LENGTH = 255;

    public function __construct(
        /** Le terme normalisé — minuscules, espaces de bord retirés. Sert de clé de regroupement. */
        #[ORM\Column(length: self::MAX_LENGTH, unique: true)]
        public string $term,
        /** Le terme tel qu'il a été tapé, pour la lecture humaine. */
        #[ORM\Column(length: self::MAX_LENGTH)]
        public string $label,
        /** Combien de fois une recherche a été lancée dessus. */
        #[ORM\Column(type: 'integer')]
        public int $occurrences = 1,
        #[ORM\Column]
        public ?DateTimeImmutable $lastSearchedAt = null,
    ) {
        parent::__construct();

        $this->lastSearchedAt ??= new DateTimeImmutable();
    }
}
