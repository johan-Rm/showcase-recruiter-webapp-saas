<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Entity\Candidate;
use App\Entity\Notification;
use App\Entity\Organization;
use App\Enum\NotificationChannel;
use App\Enum\NotificationType;
use App\Enum\RevealType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Point d'émission UNIQUE des notifications (invariant 058 : pas de tuyau parallèle). Chaque
 * événement métier notifiable passe par une méthode d'ici ; l'email s'y adossera plus tard
 * (Messenger), sans second circuit.
 *
 * `persist` seulement, JAMAIS `flush` : l'émission s'inscrit dans la transaction de l'événement
 * appelant (dévoilement…) → atomicité par construction, rollback commun (« pas de dévoilement sans
 * notification, pas de notification orpheline »).
 */
final readonly class NotificationEmitter
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Un accès (coordonnées / CV / compte rendu) vient d'être payé sur ce candidat : on le prévient.
     *
     * Anonymisé : la FK `relatedOrganization` reste interne (usage email), l'`Output` du flux ne
     * l'expose jamais. `data.access` porte le TYPE d'accès — c'est une info sur le candidat lui-même
     * (« vos coordonnées ont été consultées »), pas sur l'entreprise.
     */
    public function profileRevealed(Candidate $candidate, Organization $organization, RevealType $type): void
    {
        $this->em->persist(new Notification(
            recipient: $candidate->getPerson(),
            type: NotificationType::PROFILE_REVEALED,
            channels: [NotificationChannel::IN_APP->value],
            relatedOrganization: $organization,
            relatedCandidate: $candidate,
            data: ['access' => $type->value],
        ));
    }
}
