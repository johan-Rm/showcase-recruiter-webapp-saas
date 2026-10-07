<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\LabelReviewInput;
use App\ApiResource\Output\LabelReviewOutput;
use App\Entity\User;
use App\Entity\VocabularyLabel;
use App\Enum\VocabularyStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * POST /v1/admin/referentials/labels/reviews : déprécier ou restaurer des libellés « mot à mot »
 * (task 124).
 *
 * Réservé à ROLE_ADMIN — le référentiel est commun à toutes les entreprises. Le geste est **tracé**
 * (qui, quand) sur le libellé, comme la validation d'un concept en `120`.
 *
 * Une réingestion ne l'efface pas : `VocabularyImporter::importLabel` upsert par
 * `(concept, label, lang)` et ne met à jour que la fréquence — statut et trace survivent.
 *
 * @implements ProcessorInterface<LabelReviewInput|null, LabelReviewOutput>
 */
final readonly class LabelReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LabelReviewOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }
        if (!$data instanceof LabelReviewInput) {
            throw new BadRequestHttpException('Charge utile invalide.');
        }

        $status = VocabularyStatus::tryFrom($data->status);
        if (VocabularyStatus::DEPRECIE !== $status && VocabularyStatus::CANDIDAT !== $status) {
            throw new BadRequestHttpException('Statut attendu : DEPRECIE (exclure) ou CANDIDAT (restaurer).');
        }

        $ids = array_values(array_unique(array_filter($data->labelIds, static fn (string $i): bool => '' !== trim($i))));

        // uuid canonique → id d'entrée : un uuid invalide n'est jamais recherché, il ressort en inconnu.
        $inputByCanonical = [];
        $uuids = [];
        foreach ($ids as $id) {
            if (Uuid::isValid($id)) {
                $uuid = Uuid::fromString($id);
                $inputByCanonical[$uuid->toRfc4122()] = $id;
                $uuids[] = $uuid;
            }
        }

        $labels = [] !== $uuids
            ? $this->em->getRepository(VocabularyLabel::class)->findBy(['uuid' => $uuids])
            : [];

        $user = $this->security->getUser();
        $reviewer = $user instanceof User ? $user->getUserIdentifier() : 'inconnu';
        $now = new DateTimeImmutable();

        $found = [];
        foreach ($labels as $label) {
            $label->status = $status;
            $label->reviewedBy = $reviewer;
            $label->reviewedAt = $now;
            $found[] = $inputByCanonical[$label->getUuid()->toRfc4122()] ?? $label->getUuid()->toRfc4122();
        }
        $this->em->flush();

        return new LabelReviewOutput(
            $status->value,
            \count($labels),
            array_values(array_diff($ids, $found)),
            $reviewer,
            $now,
        );
    }
}
