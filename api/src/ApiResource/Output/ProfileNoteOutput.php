<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;

/**
 * Note collaborative sur un candidat (partagée au sein de l'entreprise).
 * V2 uniquement : volontairement non exposé comme ApiResource en V1.
 */
final class ProfileNoteOutput
{
    public function __construct(
        #[ApiProperty(identifier: false)]
        public string $id,
        public string $candidateId,
        public string $authorId,
        public string $body,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
