<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminVivierGapOutput;
use App\Domain\Reporting\QualificationGapReporter;
use App\Enum\QualificationGapReason;
use App\Enum\QualificationMetric;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/admin/vivier/gaps/{metric} : les profils qu'une qualification ne compte pas, et pourquoi
 * (task API-034). L'API garde ROLE_ADMIN, explicitement ici comme sur les autres accès cross-tenant.
 *
 * Une métrique inconnue répond 404 et jamais une liste vide : « cette métrique n'existe pas » et
 * « cette métrique n'a aucun manque » sont deux réponses différentes, et confondre les deux ferait
 * passer une faute de frappe pour une bonne nouvelle. Même règle pour un motif étranger à la
 * métrique demandée.
 *
 * Le motif n'existant dans aucune colonne, le filtre et la pagination s'appliquent APRÈS le
 * classement, en PHP — cf. {@see QualificationGapReporter}.
 *
 * @implements ProviderInterface<AdminVivierGapOutput>
 */
final readonly class AdminVivierGapProvider implements ProviderInterface
{
    private const int PER_PAGE_DEFAULT = 20;
    private const int PER_PAGE_MAX = 100;

    public function __construct(
        private QualificationGapReporter $reporter,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminVivierGapOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $metricName = $uriVariables['metric'] ?? null;
        $metric = \is_string($metricName) ? QualificationMetric::tryFrom($metricName) : null;
        if (!$metric instanceof QualificationMetric) {
            throw new NotFoundHttpException();
        }

        $request = $this->requestStack->getCurrentRequest();
        $reason = $this->reason($metric, $request?->query->get('reason'));

        $gaps = $this->reporter->gaps($metric);
        $breakdown = QualificationGapReporter::breakdown($gaps);

        $filtered = $reason instanceof QualificationGapReason
            ? array_values(array_filter($gaps, static fn (array $gap): bool => $gap['reason'] === $reason))
            : $gaps;

        $page = max(1, $request?->query->getInt('page', 1) ?? 1);
        $perPage = min(
            self::PER_PAGE_MAX,
            max(1, $request?->query->getInt('perPage', self::PER_PAGE_DEFAULT) ?? self::PER_PAGE_DEFAULT),
        );

        $items = array_map(
            static fn (array $gap): array => [...$gap, 'reason' => $gap['reason']->value],
            \array_slice($filtered, ($page - 1) * $perPage, $perPage),
        );

        return new AdminVivierGapOutput(
            $metric->value,
            $breakdown,
            $reason?->value,
            $items,
            \count($filtered),
            $page,
            $perPage,
        );
    }

    /** Le motif demandé, validé CONTRE sa métrique : un motif n'a pas de sens hors de la sienne. */
    private function reason(QualificationMetric $metric, mixed $requested): ?QualificationGapReason
    {
        if (!\is_string($requested) || '' === $requested) {
            return null;
        }

        $reason = QualificationGapReason::tryFrom($requested);
        if (!$reason instanceof QualificationGapReason || !\in_array($reason, $metric->reasons(), true)) {
            throw new NotFoundHttpException();
        }

        return $reason;
    }
}
