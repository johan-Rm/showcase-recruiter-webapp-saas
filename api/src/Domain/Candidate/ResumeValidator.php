<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

/**
 * Garde-fous du CV d'inscription AVANT tout envoi à Jarvi : un seul fichier, PDF réel (magie
 * `%PDF-`, jamais le seul Content-Type client), taille décodée bornée. Le binaire n'est pas copié
 * en base showcase-recruiter-webapp-saas : la validation ne retourne que le base64 nu et un nom de fichier sûr.
 */
final readonly class ResumeValidator
{
    /** 5 Mio de PDF décodé : au-delà, on refuse avant d'encoder/pousser (poids base64). */
    public const int MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @return array{fileName: string, content: string}|null null si aucun CV fourni
     *
     * @throws InvalidResumeException
     */
    public static function validate(?string $fileName, ?string $content): ?array
    {
        if (null === $content || '' === $content) {
            return null;
        }

        $decoded = base64_decode($content, true);
        if (false === $decoded) {
            throw new InvalidResumeException('CV illisible : encodage base64 invalide.');
        }
        if (\strlen($decoded) > self::MAX_BYTES) {
            throw new InvalidResumeException('CV trop volumineux (maximum 5 Mio).');
        }
        if (!str_starts_with($decoded, '%PDF-')) {
            throw new InvalidResumeException('CV refusé : seul le format PDF est accepté.');
        }

        return [
            'fileName' => self::safeFileName($fileName),
            'content' => $content,
        ];
    }

    private static function safeFileName(?string $fileName): string
    {
        $base = basename(trim((string) $fileName));
        if ('' === $base || '.' === $base) {
            return 'cv.pdf';
        }

        return str_ends_with(mb_strtolower($base), '.pdf') ? $base : $base.'.pdf';
    }
}
