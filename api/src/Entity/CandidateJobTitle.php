<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Intitulé de poste porté par un candidat, tel qu'écrit dans son parcours — DÉRIVÉ à l'ingestion
 * (task API-037).
 *
 * POURQUOI UNE TABLE DE PLUS. {@see CandidateJobExperience} porte une ligne par métier **reconnu**
 * par le dictionnaire : un intitulé qu'il ignore n'y a aucune ligne où s'écrire, et le profil qui le
 * porte devient introuvable par le critère de poste. Or la traîne est énorme — 5 439 intitulés du
 * vivier n'apparaissent qu'une fois, et aucun rangement du dictionnaire ne la rattrapera. Cette
 * table est le filet en dessous : elle rend le texte cherchable, indépendamment de ce que le
 * dictionnaire sait en faire.
 *
 * LES DEUX TABLES NE COMPTENT PAS LA MÊME CHOSE et ne se remplacent pas. L'une porte le SENS —
 * reconnu, élargi aux synonymes, cumulé en durée. L'autre porte la LETTRE, sans expansion ni
 * traduction. C'est ce qui rend le repli honnête : il trouve ce qui est écrit, il ne devine rien.
 *
 * FORME CANONIQUE, celle que produit {@see \App\Domain\Vocabulary\TermNormalizer::canonicalizeTitle()}
 * — la même que voit la reconnaissance. Deux écritures du même intitulé compteraient sinon deux
 * fois, et l'écart entre ce que la recherche compare et ce que le dictionnaire compare deviendrait
 * invisible.
 *
 * Champ DÉRIVÉ : intégralement recalculé à chaque réingestion ou reprojection, jamais saisi.
 */
#[ORM\Entity]
#[ORM\Table(name: 'candidate_job_title')]
#[ORM\UniqueConstraint(name: 'uniq_candidate_job_title', columns: ['candidate_id', 'title'])]
#[ORM\Index(name: 'idx_candidate_job_title', columns: ['title'])]
class CandidateJobTitle extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'jobTitles')]
        #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        public Candidate $candidate,
        /** Mesuré sur le vivier : 100 caractères au maximum avant canonicalisation, 28 en moyenne. */
        #[ORM\Column(length: 190)]
        public string $title,
    ) {
        parent::__construct();
    }
}
