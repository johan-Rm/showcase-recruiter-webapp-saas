<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminVivierOutput;
use App\Domain\Reporting\QualificationReporter;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/admin/vivier : état de la base qualifiée (tâche 119), gardé ROLE_ADMIN.
 *
 * Délègue à {@see QualificationReporter}, la MÊME source que `app:qualification:report` : l'écran
 * et le CLI ne peuvent pas diverger. Lecture des tables déjà agrégées à l'ingestion, jamais un
 * rescan des JSON.
 *
 * @implements ProviderInterface<AdminVivierOutput>
 */
final readonly class AdminVivierProvider implements ProviderInterface
{
    public function __construct(
        private QualificationReporter $reporter,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminVivierOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        return new AdminVivierOutput(
            $this->reporter->snapshot(),
            $this->reporter->distributions(),
            $this->reporter->fillRate(),
            (new DateTimeImmutable())->format('c'),
        );
    }
}
