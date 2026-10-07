<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Proximité entre deux concepts DISTINCTS (task 111) — à ne pas confondre avec la synonymie, qui
 * n'est pas une relation mais plusieurs libellés sur un même {@see VocabularyConcept}.
 *
 * Relation SYMÉTRIQUE stockée UNE fois, `conceptA` portant toujours le plus petit identifiant :
 * deux lignes par paire se désynchroniseraient. Une lecture « proches de X » interroge donc les
 * deux colonnes.
 *
 * Champ DÉRIVÉ, entièrement recalculé par `app:vocabulary:similarity` — la table est PURGÉE puis
 * réinsérée à chaque passe. C'est pourquoi la relation ne porte **aucun statut de validation** : il
 * serait effacé au premier rerun, sans un mot. La confiance se **dérive du concept** (task 113) —
 * les voisins d'un concept validé héritent de sa confiance.
 */
#[ORM\Entity]
#[ORM\Table(name: 'vocabulary_concept_similarity')]
#[ORM\UniqueConstraint(name: 'uniq_vocabulary_similarity_pair', columns: ['concept_a_id', 'concept_b_id'])]
#[ORM\Index(name: 'idx_vocabulary_similarity_a', columns: ['concept_a_id'])]
#[ORM\Index(name: 'idx_vocabulary_similarity_b', columns: ['concept_b_id'])]
class VocabularyConceptSimilarity extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: VocabularyConcept::class)]
        #[ORM\JoinColumn(name: 'concept_a_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public VocabularyConcept $conceptA,
        #[ORM\ManyToOne(targetEntity: VocabularyConcept::class)]
        #[ORM\JoinColumn(name: 'concept_b_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public VocabularyConcept $conceptB,
        /** Profils portant les deux concepts — l'explication lisible d'un rapprochement. */
        #[ORM\Column(type: 'integer')]
        public int $cooccurrences,
        #[ORM\Column(type: 'float')]
        public float $jaccard,
        /** Score de classement : éteint les concepts trop fréquents (cf. ConceptSimilarityCalculator). */
        #[ORM\Column(type: 'float')]
        public float $npmi,
    ) {
        parent::__construct();
    }
}
