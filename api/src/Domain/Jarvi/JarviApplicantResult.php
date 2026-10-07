<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Réponse `201` de `POST /applicants`. `profileId` est l'ancre Jarvi réelle (future
 * `Candidate.jarviProfileId`). `taskId` identifie l'extraction asynchrone. `fileErrors` liste les
 * CV refusés par Jarvi (upload accepté mais parsing en erreur) — journalisé, non bloquant.
 */
final readonly class JarviApplicantResult
{
    /**
     * @param list<array<string, mixed>> $fileErrors
     */
    public function __construct(
        public string $profileId,
        public ?string $taskId = null,
        public array $fileErrors = [],
    ) {
    }
}
