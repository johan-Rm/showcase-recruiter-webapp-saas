<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\AdminReferentialOutput;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /v1/admin/referentials/{type} : une page de concepts à relire (tâche 120). Garde ROLE_ADMIN
 * explicite — la validation engage la plateforme entière, le référentiel étant commun.
 *
 * Tri par fréquence décroissante et couverture cumulée : la relecture se priorise, elle ne
 * s'épuise pas. Les libellés accompagnent chaque concept — c'est l'assemblage de synonymes qui est
 * jugé, pas le seul nom canonique.
 *
 * @implements ProviderInterface<AdminReferentialOutput>
 */
final readonly class AdminReferentialProvider implements ProviderInterface
{
    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private Connection $connection,
        private Security $security,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminReferentialOutput
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $type = VocabularyType::tryFrom(mb_strtoupper((string) ($uriVariables['type'] ?? '')));
        if (!$type instanceof VocabularyType) {
            throw new NotFoundHttpException('Référentiel inconnu : attendu METIER ou COMPETENCE.');
        }

        $request = $this->requestStack->getCurrentRequest();
        $page = max(1, (int) $request?->query->get('page', '1'));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) $request?->query->get('perPage', '25')));
        $status = VocabularyStatus::tryFrom(mb_strtoupper((string) $request?->query->get('status', '')));

        return new AdminReferentialOutput(
            $type->value,
            $this->items($type, $status, $page, $perPage),
            $this->total($type, $status),
            $page,
            $perPage,
            $this->countsByStatus($type),
            $this->totalFrequency($type),
        );
    }

    /**
     * La couverture cumulée se calcule sur TOUT le type, pas sur la page : « valider jusqu'ici
     * couvre X % » n'aurait aucun sens remis à zéro à chaque page.
     *
     * Les libellés sont renvoyés STRUCTURÉS (uuid + statut), pas en texte agrégé : chacun est
     * adressable pour être déprécié « mot à mot » (task 124), et porte son propre statut.
     *
     * @return list<array{code: ?string, label: string, status: string, frequency: int, labels: list<array{uuid: string, label: string, lang: string, status: string, frequency: int}>, cumulativeCoverage: float}>
     */
    private function items(VocabularyType $type, ?VocabularyStatus $status, int $page, int $perPage): array
    {
        $params = ['type' => $type->value];
        $filter = '';
        if ($status instanceof VocabularyStatus) {
            $filter = ' AND c.status = :status';
            $params['status'] = $status->value;
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT c.code, c.pref_label, c.status, c.frequency,
                       coalesce(
                           (SELECT json_agg(json_build_object(
                                       'uuid', l.uuid, 'label', l.label, 'lang', l.lang,
                                       'status', l.status, 'frequency', l.frequency
                                   ) ORDER BY l.frequency DESC)
                            FROM vocabulary_label l WHERE l.concept_id = c.id AND l.deleted_at IS NULL),
                           '[]'
                       ) AS labels,
                       sum(c.frequency) OVER (ORDER BY c.frequency DESC, c.id) AS running
                FROM vocabulary_concept c
                WHERE c.deleted_at IS NULL AND c.type = :type{$filter}
                ORDER BY c.frequency DESC, c.id
                LIMIT :limit OFFSET :offset
                SQL,
            $params + ['limit' => $perPage, 'offset' => ($page - 1) * $perPage],
        );

        $total = max(1, $this->totalFrequency($type));

        return array_map(static fn (array $r): array => [
            'code' => null !== $r['code'] ? (string) $r['code'] : null,
            'label' => (string) $r['pref_label'],
            'status' => (string) $r['status'],
            'frequency' => (int) $r['frequency'],
            'labels' => self::labels($r['labels']),
            'cumulativeCoverage' => round((int) $r['running'] / $total, 4),
        ], $rows);
    }

    /**
     * @return list<array{uuid: string, label: string, lang: string, status: string, frequency: int}>
     */
    private static function labels(mixed $json): array
    {
        $decoded = json_decode((string) $json, true);
        if (!\is_array($decoded)) {
            return [];
        }

        /* @var list<array<string, mixed>> $decoded */
        return array_map(static fn (array $l): array => [
            'uuid' => (string) $l['uuid'],
            'label' => (string) $l['label'],
            'lang' => (string) $l['lang'],
            'status' => (string) $l['status'],
            'frequency' => (int) $l['frequency'],
        ], $decoded);
    }

    private function total(VocabularyType $type, ?VocabularyStatus $status): int
    {
        $params = ['type' => $type->value];
        $filter = '';
        if ($status instanceof VocabularyStatus) {
            $filter = ' AND status = :status';
            $params['status'] = $status->value;
        }

        return (int) $this->connection->fetchOne(
            "SELECT count(*) FROM vocabulary_concept WHERE deleted_at IS NULL AND type = :type{$filter}",
            $params,
        );
    }

    /** @return array<string, int> */
    private function countsByStatus(VocabularyType $type): array
    {
        $counts = array_fill_keys(array_map(static fn (VocabularyStatus $s): string => $s->value, VocabularyStatus::cases()), 0);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT status, count(*) AS n FROM vocabulary_concept WHERE deleted_at IS NULL AND type = :type GROUP BY status',
            ['type' => $type->value],
        );
        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }

        return $counts;
    }

    private function totalFrequency(VocabularyType $type): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT coalesce(sum(frequency), 0) FROM vocabulary_concept WHERE deleted_at IS NULL AND type = :type',
            ['type' => $type->value],
        );
    }
}
