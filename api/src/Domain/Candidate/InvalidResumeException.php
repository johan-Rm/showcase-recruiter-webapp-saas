<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use RuntimeException;

/**
 * CV joint à l'inscription non conforme (format non-PDF, base64 invalide ou trop volumineux).
 * Mappée en 422 par le processor : le fichier n'est jamais transmis à Jarvi.
 */
final class InvalidResumeException extends RuntimeException
{
}
