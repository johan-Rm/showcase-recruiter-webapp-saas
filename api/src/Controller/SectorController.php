<?php

declare(strict_types=1);

namespace App\Controller;

use App\State\SectorReferential;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Liste fermée des secteurs d'activité, pour le dropdown d'inscription entreprise.
 *
 * Public : le formulaire d'inscription n'est pas authentifié. Un simple contrôleur suffit — la liste
 * est un tableau de libellés, pas une ressource métier ; la faire passer par API Platform n'apporterait
 * qu'une enveloppe. Même source que la validation (SectorReferential) : jamais désynchronisés.
 */
final class SectorController
{
    #[Route('/v1/sectors', name: 'api_sectors', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(SectorReferential::labels());
    }
}
