<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sonde de disponibilité (publique) — utilisée par les healthchecks Docker / déploiement.
 */
final class HealthController
{
    #[Route('/v1/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'service' => 'showcase-recruiter-webapp-saas-api',
            'time' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ]);
    }
}
