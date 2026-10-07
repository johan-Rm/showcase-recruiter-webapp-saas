<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use RuntimeException;

/**
 * Échec d'une écriture montante vers Jarvi (tâche 016) : réponse inattendue ou indisponibilité.
 * Captée par la synchro pour marquer le candidat FAILED (retry au passage suivant).
 */
final class JarviWriteException extends RuntimeException
{
}
