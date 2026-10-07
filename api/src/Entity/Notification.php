<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationType;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notification adressée à un Person (in-app si compte, email via contactEmail). Push = V2.
 * `data` = contexte non-relationnel (compteurs, libellés) ; les entités liées sont des FK.
 * Cf. data-model.md § Notifications.
 *
 * Index (recipient_id, uuid) : le flux à curseur lit « mes notifications après {uuid} » — l'uuid v7
 * est chronologiquement trié, l'id bigint interne n'étant jamais exposé (cf. tâche 058).
 *
 * Pas de `repositoryClass` (deptrac : Entity ne dépend que d'Enum) : `NotificationRepository` est
 * un service ServiceEntityRepository injecté directement là où il sert.
 */
#[ORM\Entity]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_notification_recipient_uuid', columns: ['recipient_id', 'uuid'])]
class Notification extends AbstractEntity
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Person::class)]
        #[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: false)]
        public Person $recipient,
        #[ORM\Column(enumType: NotificationType::class)]
        public NotificationType $type,
        /** @var list<string> canaux IN_APP / EMAIL (NotificationChannel::value) */
        #[ORM\Column(type: 'json')]
        public array $channels = [],
        #[ORM\ManyToOne(targetEntity: Organization::class)]
        #[ORM\JoinColumn(name: 'related_organization_id', referencedColumnName: 'id', nullable: true)]
        public ?Organization $relatedOrganization = null,
        #[ORM\ManyToOne(targetEntity: Candidate::class)]
        #[ORM\JoinColumn(name: 'related_candidate_id', referencedColumnName: 'id', nullable: true)]
        public ?Candidate $relatedCandidate = null,
        /** @var array<string, mixed>|null contexte non-relationnel (aucun id/relation) */
        #[ORM\Column(type: 'json', nullable: true)]
        public ?array $data = null,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $readAt = null,
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $emailSentAt = null,
    ) {
        parent::__construct();
    }
}
