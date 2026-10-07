<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use RuntimeException;

/**
 * Échec d'un appel d'enrichissement (transport, réponse illisible, refus du modèle). L'import et la
 * récolte n'en dépendent pas : un enrichissement qui échoue ne casse jamais le pipeline courant.
 */
final class VocabularyEnrichException extends RuntimeException
{
}
