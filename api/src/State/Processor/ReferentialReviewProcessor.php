<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\ReferentialReviewInput;
use App\ApiResource\Output\ReferentialReviewOutput;
use App\Entity\User;
use App\Entity\VocabularyConcept;
use App\Enum\VocabularyStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * POST /v1/admin/referentials/reviews : promotion ou dépréciation de concepts (tâche 120).
 *
 * Réservé à ROLE_ADMIN : le référentiel est commun à toutes les entreprises, une validation engage
 * la plateforme entière.
 *
 * Le geste est **tracé** (qui, quand) — c'est l'exigence de `113`, et c'est ce qui distingue une
 * décision humaine d'un statut posé par la machine.
 *
 * Une réingestion ne l'efface pas : `VocabularyImporter` ne rétrograde jamais sur un `candidat` de
 * fichier, qui signifie « pas encore relu » et non « rejeté ».
 *
 * @implements ProcessorInterface<ReferentialReviewInput|null, ReferentialReviewOutput>
 */
final readonly class ReferentialReviewProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReferentialReviewOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }
        if (!$data instanceof ReferentialReviewInput) {
            throw new BadRequestHttpException('Charge utile invalide.');
        }

        $status = VocabularyStatus::tryFrom($data->status);
        if (!$status instanceof VocabularyStatus || VocabularyStatus::CANDIDAT === $status) {
            throw new BadRequestHttpException('Statut attendu : VALIDE ou DEPRECIE.');
        }

        $codes = array_values(array_unique(array_filter($data->codes, static fn (string $c): bool => '' !== trim($c))));
        $concepts = [] !== $codes
            ? $this->em->getRepository(VocabularyConcept::class)->findBy(['code' => $codes])
            : [];

        $user = $this->security->getUser();
        $reviewer = $user instanceof User ? $user->getUserIdentifier() : 'inconnu';
        $now = new DateTimeImmutable();

        foreach ($concepts as $concept) {
            $concept->status = $status;
            $concept->reviewedBy = $reviewer;
            $concept->reviewedAt = $now;
        }
        $this->em->flush();

        // Les codes non trouvés sont rendus tels quels : mieux vaut un retour honnête qu'un
        // décompte qui laisse croire que tout est passé.
        $found = array_map(static fn (VocabularyConcept $c): string => (string) $c->code, $concepts);

        return new ReferentialReviewOutput(
            $status->value,
            \count($concepts),
            array_values(array_diff($codes, $found)),
            $reviewer,
            $now,
        );
    }
}
