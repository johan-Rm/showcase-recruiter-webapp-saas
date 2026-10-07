<?php

declare(strict_types=1);

namespace App\Serializer;

use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\CandidateOutput;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalisation directe des DTO candidat (carte et détail) : propriétés publiques déjà scalaires,
 * pas besoin de la réflexion/métadonnées du normaliseur générique — mesuré ~2× plus rapide sur une
 * page de 1 626 cartes. Reproduit le contrat existant : nulls omis, dates au format ATOM.
 */
final class CandidateOutputNormalizer implements NormalizerInterface
{
    /** @return array<string, mixed> */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalized = [];
        foreach (get_object_vars($data) as $property => $value) {
            if (null === $value) {
                continue;
            }
            $normalized[$property] = $value instanceof DateTimeInterface
                ? $value->format(DateTimeInterface::ATOM)
                : $value;
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CandidateCardOutput || $data instanceof CandidateOutput;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CandidateCardOutput::class => true,
            CandidateOutput::class => true,
        ];
    }
}
