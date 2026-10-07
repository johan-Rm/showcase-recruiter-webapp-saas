<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Réconciliation quotidienne du périmètre (tâche 020) : les projections actives dont l'ancre n'est
 * plus dans le périmètre Jarvi sont DÉSACTIVÉES (`active = false`), jamais supprimées (historique,
 * dévoilés payés, preuve RGPD). Symétriquement, une projection désactivée qui REVIENT dans le
 * périmètre est RÉACTIVÉE (sinon un profil brièvement sorti resterait masqué à tort).
 */
final readonly class PerimeterReconciler
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateRepository $candidates,
        private JarviClientInterface $client,
    ) {
    }

    public function reconcile(bool $dryRun = false, ?int $limit = null): ReconciliationReport
    {
        $perimeter = $this->client->listPerimeterProfileIds($limit);

        // Garde-fou : un périmètre vide est très probablement un échec de fetch, pas une vraie
        // sortie de tout le monde → on ne touche à rien dans ce cas.
        if ([] === $perimeter) {
            return new ReconciliationReport(0, 0, 0);
        }

        $inPerimeter = array_fill_keys($perimeter, true);

        $deactivated = 0;
        foreach ($this->candidates->findActive() as $candidate) {
            if (!isset($inPerimeter[$candidate->jarviProfileId->toString()])) {
                if (!$dryRun) {
                    $candidate->active = false;
                }
                ++$deactivated;
            }
        }

        $reactivated = 0;
        foreach ($this->candidates->findInactive() as $candidate) {
            if (isset($inPerimeter[$candidate->jarviProfileId->toString()])) {
                if (!$dryRun) {
                    $candidate->active = true;
                }
                ++$reactivated;
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        return new ReconciliationReport(\count($perimeter), $deactivated, $reactivated);
    }
}
