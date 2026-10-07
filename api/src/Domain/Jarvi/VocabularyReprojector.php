<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\TitleConceptResolver;
use App\Entity\Candidate;
use App\Entity\VocabularyConcept;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Rejoue le dictionnaire sur le vivier DÉJÀ chargé, sans repasser par Jarvi (task API-035).
 *
 * POURQUOI. Le rattachement d'un profil à un métier est calculé à l'INGESTION : corriger
 * `taxonomy.json` puis le charger ne déplaçait donc aucun profil. Un concept créé pour distinguer
 * « architecte d'intérieur » d'« architecte logiciel » restait sans personne, donc invisible à
 * l'auto-complétion, qui ne propose que ce qu'au moins un profil exerce. La correction n'était
 * observable qu'après une réingestion complète — coûteuse, dépendante de Jarvi, et sans rapport avec
 * ce qu'on vient de changer.
 *
 * CE QU'IL RECALCULE, ET RIEN D'AUTRE : les métiers exercés (`candidate_job_experience`) et le
 * concept du poste actuel. Ce sont exactement les deux projections que le dictionnaire détermine.
 * L'intitulé, l'employeur, la certitude et l'ancienneté du poste actuel restent tels que l'ingestion
 * les a posés — ils ne dépendent pas du vocabulaire, et les recalculer ferait dériver une durée
 * mesurée à une autre date.
 *
 * IL POSE AUSSI LES INTITULÉS BRUTS au passage (task API-037), qui ne dépendent pourtant pas du
 * dictionnaire. C'est délibéré : le vivier déjà chargé n'en porte aucun, et la reprojection est le
 * seul chemin qui les lui donne sans réingestion complète. Une fois posés, ils ne bougent plus.
 *
 * IL LIT LA RÉPLICA, jamais Jarvi : `candidate_jarvi.work_experiences` conserve les positions dans la
 * forme même que reçoit l'ingestion. La reprojection est donc rejouable hors ligne, sans clé, et
 * autant de fois qu'on veut.
 */
final readonly class VocabularyReprojector
{
    /** L'Unit of Work retient chaque entité et une copie de ses données : on écrit par lots. */
    private const int BATCH = 200;

    public function __construct(
        private EntityManagerInterface $em,
        private JobProjectionSynchronizer $jobProjections,
        private TitleConceptResolver $resolver,
    ) {
    }

    /**
     * @param callable(int, int): void|null $onBatch appelé après chaque lot écrit, avec sa taille et
     *                                               le total — sert l'avancement affiché, jamais la
     *                                               logique
     */
    public function reproject(?int $limit = null, bool $dryRun = false, ?callable $onBatch = null): VocabularyReprojectReport
    {
        $ids = $this->candidateIds($limit);
        $total = \count($ids);
        $report = new VocabularyReprojectReport($total);

        foreach (array_chunk($ids, self::BATCH) as $chunk) {
            foreach ($this->em->getRepository(Candidate::class)->findBy(['id' => $chunk]) as $candidate) {
                $jarvi = $candidate->getJarvi();
                if (null === $jarvi) {
                    continue;
                }

                $report->moved += $this->jobProjections->sync($candidate, $jarvi->workExperiences);

                $conceptId = CurrentPositionExtractor::fromPositions($jarvi->workExperiences, $this->resolver)->conceptId;
                $current = null !== $conceptId
                    ? $this->em->getReference(VocabularyConcept::class, $conceptId)
                    : null;
                if ($current?->getId() !== $jarvi->currentJobConcept?->getId()) {
                    $jarvi->currentJobConcept = $current;
                    ++$report->retitled;
                }
            }

            if (!$dryRun) {
                $this->em->flush();
            }
            $this->em->clear();
            if (null !== $onBatch) {
                $onBatch(\count($chunk), $total);
            }
        }

        return $report;
    }

    /** @return list<int> */
    private function candidateIds(?int $limit): array
    {
        $sql = 'SELECT c.id FROM candidate c JOIN candidate_jarvi j ON j.candidate_id = c.id'
            .' WHERE c.deleted_at IS NULL AND j.deleted_at IS NULL ORDER BY c.id';
        if (null !== $limit) {
            $sql .= ' LIMIT '.$limit;
        }

        return array_map(intval(...), $this->em->getConnection()->fetchFirstColumn($sql));
    }
}
