<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Entity\Candidate;
use App\Entity\ProfileView;
use App\Entity\Recruiter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Trace la consultation d'un profil par un recruteur (brief §4, tâche API-072). Visiter n'est pas
 * dévoiler : un recruteur ouvre des fiches masquées sans dépenser de crédit, et c'est cet écart que
 * l'historique doit montrer.
 *
 * Une seule visite par recruteur, par candidat et par jour civil : revenir trois fois sur la même
 * fiche dans l'après-midi ne dit pas trois intérêts, et le compteur montré au candidat
 * (« consulté X fois ») sur-compterait une seule personne.
 */
final readonly class ProfileViewRecorder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function record(Candidate $candidate, Recruiter $recruiter): void
    {
        try {
            if ($this->alreadySeenToday($candidate, $recruiter)) {
                return;
            }

            $this->entityManager->persist(new ProfileView($candidate, $recruiter->organization, $recruiter));
            $this->entityManager->flush();
        } catch (Throwable $error) {
            // Le recruteur a demandé une fiche : il la reçoit. Une trace perdue est un incident
            // mineur, une fiche perdue est une panne.
            $this->logger->error('Visite de profil non enregistrée.', [
                'candidate' => $candidate->getId(),
                'recruiter' => $recruiter->getId(),
                'exception' => $error,
            ]);
        }
    }

    /**
     * Le contrôle et l'insertion ne sont pas atomiques : deux onglets ouverts en même temps peuvent
     * créer un doublon. Toléré — une ligne de trop dans un fil, un compteur supérieur de un —,
     * là où verrouiller une lecture coûterait plus que le défaut évité.
     */
    private function alreadySeenToday(Candidate $candidate, Recruiter $recruiter): bool
    {
        $count = $this->entityManager
            ->createQuery(
                'SELECT COUNT(v.id) FROM App\Entity\ProfileView v
                 WHERE v.candidate = :candidate AND v.recruiter = :recruiter AND v.createdAt >= :since'
            )
            ->setParameter('candidate', $candidate)
            ->setParameter('recruiter', $recruiter)
            ->setParameter('since', new DateTimeImmutable('today'))
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
