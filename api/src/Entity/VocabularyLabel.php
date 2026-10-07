<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\VocabularyLabelSource;
use App\Enum\VocabularyStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un libellé rattaché à un {@see VocabularyConcept} — c'est le grain de la synonymie : tous les
 * libellés d'un même concept en sont synonymes (« anglais » = « english », « PO » = « Product
 * Owner »). `frequency` = occurrences vues dans le vivier ; `source` trace la provenance pour la
 * maintenance. L'unicité (concept, label, lang) empêche les doublons après normalisation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'vocabulary_label')]
#[ORM\UniqueConstraint(name: 'uniq_vocabulary_label', columns: ['concept_id', 'label', 'lang'])]
#[ORM\Index(name: 'idx_vocabulary_label_label', columns: ['label'])]
class VocabularyLabel extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: VocabularyConcept::class, inversedBy: 'labels')]
        #[ORM\JoinColumn(name: 'concept_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public VocabularyConcept $concept,
        #[ORM\Column(length: 190)]
        public string $label,
        #[ORM\Column(length: 8)]
        public string $lang = 'fr',
        #[ORM\Column(enumType: VocabularyLabelSource::class)]
        public VocabularyLabelSource $source = VocabularyLabelSource::JARVI,
        #[ORM\Column(type: 'integer')]
        public int $frequency = 0,
        #[ORM\Column(enumType: VocabularyStatus::class)]
        public VocabularyStatus $status = VocabularyStatus::CANDIDAT,
        // Traçabilité de la relecture humaine (task 124) : QUI a déprécié/restauré ce libellé et
        // QUAND. Nul tant qu'aucun geste humain n'a tranché — la réingestion ne les touche jamais.
        #[ORM\Column(length: 190, nullable: true)]
        public ?string $reviewedBy = null,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $reviewedAt = null,
    ) {
        parent::__construct();
    }
}
