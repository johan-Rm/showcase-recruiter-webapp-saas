<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Coordonnées lues dans la réplica Jarvi (`CandidateJarvi::$contacts` / `$rawPayload`).
 *
 * Jarvi les fournit MULTI-VALUÉES — jusqu'à 3 e-mails ou numéros par profil, chacun avec sa source,
 * doublons compris (même numéro en `06…`, `00212…` et `+212…`). Le modèle showcase-recruiter-webapp-saas est mono-valué :
 * on tranche par priorité de source **MANUAL > RESUME > LINKEDIN** (à source égale, la première
 * rencontrée), conformément au cadrage import_profils_jarvi.md § Coordonnées multi-valuées.
 *
 * Aplatir ici, à la lecture, et non à l'ingestion : la réplica reste la vérité Jarvi intacte.
 */
final class JarviContacts
{
    /** Plus l'indice est bas, plus la source prime. Source inconnue ou absente : en dernier. */
    private const array SOURCE_PRIORITY = ['MANUAL' => 0, 'RESUME' => 1, 'LINKEDIN' => 2];

    private const int UNKNOWN_SOURCE_PRIORITY = 3;

    /** @param list<array<string, mixed>> $contacts */
    public static function email(array $contacts): ?string
    {
        return self::best($contacts, 'email');
    }

    /** @param list<array<string, mixed>> $contacts */
    public static function phone(array $contacts): ?string
    {
        $phone = self::best($contacts, 'phone');

        return null !== $phone ? self::toE164($phone) : null;
    }

    /**
     * URL LinkedIn : Jarvi la porte dans `profileUrl`, conservé tel quel dans le payload brut.
     *
     * @param array<string, mixed>|null $rawPayload
     */
    public static function linkedinUrl(?array $rawPayload): ?string
    {
        $url = $rawPayload['profileUrl'] ?? null;

        return \is_string($url) && '' !== $url ? $url : null;
    }

    /**
     * @param list<array<string, mixed>> $contacts
     */
    private static function best(array $contacts, string $type): ?string
    {
        $best = null;
        $bestPriority = \PHP_INT_MAX;

        foreach ($contacts as $contact) {
            if (($contact['type'] ?? null) !== $type) {
                continue;
            }

            $value = $contact['value'] ?? null;
            if (!\is_string($value) || '' === $value) {
                continue;
            }

            $source = \is_string($contact['source'] ?? null) ? $contact['source'] : '';
            $priority = self::SOURCE_PRIORITY[$source] ?? self::UNKNOWN_SOURCE_PRIORITY;
            if ($priority < $bestPriority) {
                $best = $value;
                $bestPriority = $priority;
            }
        }

        return $best;
    }

    /**
     * Normalisation E.164 de ce qui l'est déjà, à l'indicatif près : séparateurs retirés (espaces,
     * points, tirets — les seuls observés sur le vivier), préfixe international `00` ramené à `+`.
     *
     * Un numéro NATIONAL (`0603584617`) est laissé tel quel : sans pays, le compléter serait une
     * invention — mieux vaut afficher ce que Jarvi détient qu'un numéro faux. Même prudence pour
     * les notations non rencontrées (`+33 (0)6…`) : intouchées plutôt que silencieusement fausses.
     */
    private static function toE164(string $phone): string
    {
        $compact = preg_replace('/[\s.\-]/', '', $phone) ?? $phone;

        if (str_starts_with($compact, '00')) {
            $compact = '+'.substr($compact, 2);
        }

        return $compact;
    }
}
