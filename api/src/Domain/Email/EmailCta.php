<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Bouton d'un e-mail transactionnel. Il n'y en a qu'un : un e-mail qui propose deux actions n'en
 * fait faire aucune.
 */
final readonly class EmailCta
{
    public function __construct(
        public string $label,
        public string $url,
    ) {
    }
}
