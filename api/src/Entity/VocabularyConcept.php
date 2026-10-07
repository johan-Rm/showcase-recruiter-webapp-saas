<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Concept canonique du vocabulaire métiers/compétences (cf. dev-book/resources/taxonomie/README.md).
 *
 * Un concept = un SENS ; ses libellés (synonymes, dont FR/EN) vivent dans {@see VocabularyLabel}.
 * La synonymie n'est donc PAS une relation entre concepts : c'est plusieurs libellés sur un même
 * concept. `frequency` agrège la présence dans le vivier — un simple compteur rafraîchi à la
 * récolte, SANS lien vers les profils (la taxonomie ne référence aucun candidat).
 *
 * `romeCode` / `escoUri` rattachent le concept aux référentiels publics quand un match existe
 * (enrichissement `candidat`, souvent nul). Ils servent l'interopérabilité, pas le runtime.
 */
#[ORM\Entity]
#[ORM\Table(name: 'vocabulary_concept')]
#[ORM\UniqueConstraint(name: 'uniq_vocabulary_concept_type_label', columns: ['type', 'pref_label'])]
#[ORM\UniqueConstraint(name: 'uniq_vocabulary_concept_code', columns: ['code'])]
class VocabularyConcept extends AbstractEntity
{
    /** @var Collection<int, VocabularyLabel> */
    #[ORM\OneToMany(mappedBy: 'concept', targetEntity: VocabularyLabel::class)]
    public Collection $labels;

    public function __construct(
        #[ORM\Column(enumType: VocabularyType::class)]
        public VocabularyType $type,
        #[ORM\Column(length: 190)]
        public string $prefLabel,
        /**
         * Identifiant STABLE porté par `taxonomy.json` (`competence-001`, `poste-003`) — c'est LUI
         * qui identifie un concept à l'import, pas le libellé (task 113).
         *
         * Sans lui, renommer « Ingénieur d'études » en « Ingénieur d'étude » créerait un second
         * concept `candidat` et orphelinerait le validé : le travail de relecture serait perdu en
         * silence. Nullable le temps que le stock existant se recale ; l'import le renseigne.
         */
        #[ORM\Column(length: 32, nullable: true)]
        public ?string $code = null,
        #[ORM\Column(enumType: VocabularyStatus::class)]
        public VocabularyStatus $status = VocabularyStatus::CANDIDAT,
        #[ORM\Column(length: 16, nullable: true)]
        public ?string $romeCode = null,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $escoUri = null,
        #[ORM\Column(type: 'integer')]
        public int $frequency = 0,
        // Traçabilité de la relecture humaine (task 113/120) : QUI a tranché et QUAND. Nul tant que
        // le statut vient du fichier ou de la machine — seul un geste humain les renseigne.
        #[ORM\Column(length: 190, nullable: true)]
        public ?string $reviewedBy = null,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $reviewedAt = null,
    ) {
        parent::__construct();
        $this->labels = new ArrayCollection();
    }
}
