<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\SearchExportInput;
use App\State\SearchExportHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/searches/{searchId}/export (task 154, ex-task 077).
 *
 * Rend un FICHIER et non une ressource sérialisée : API Platform laisse passer une Response telle
 * quelle. L'accusé RGPD et la présence d'une sélection sont validés en amont par SearchExportInput —
 * sans eux, on n'arrive pas ici.
 *
 * @implements ProcessorInterface<mixed, Response>
 */
final readonly class SearchExportProcessor implements ProcessorInterface
{
    public function __construct(private SearchExportHandler $handler)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        if (!$data instanceof SearchExportInput) {
            throw new UnprocessableEntityHttpException('Payload invalide.');
        }

        $searchId = $uriVariables['searchId'] ?? null;

        return $this->handler->export(\is_string($searchId) ? $searchId : '', $data->candidateIds);
    }
}
