<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

/**
 * Dérivation du numéro joignable depuis l'identifiant WhatsApp de Jarvi (JID).
 *
 * Jarvi n'expose pas un numéro WhatsApp distinct : il marque un `phoneNumber` d'un JID
 * `212600000000@s.whatsapp.net` (parfois suffixé du terminal, `…:12@…`). Le JID CONTIENT donc le
 * numéro en clair — d'où l'exposition au seul profil dévoilé, comme les autres coordonnées.
 */
final class WhatsAppIdentifier
{
    /** Bornes E.164 : au plus 15 chiffres, indicatif compris ; en deçà de 8, ce n'est pas un numéro. */
    private const int MIN_DIGITS = 8;
    private const int MAX_DIGITS = 15;

    /**
     * @param list<array<string, mixed>> $contacts réplica `CandidateJarvi::$contacts`
     */
    public static function fromContacts(array $contacts): ?string
    {
        foreach ($contacts as $contact) {
            $number = \is_string($contact['whatsappIdentifier'] ?? null)
                ? self::toE164($contact['whatsappIdentifier'])
                : null;
            if (null !== $number) {
                return $number;
            }
        }

        return null;
    }

    public static function toE164(string $jid): ?string
    {
        $digits = strstr($jid, '@', true);
        if (false === $digits) {
            return null;
        }

        // Suffixe d'appareil (« :12 ») présent sur certains JID : on ne garde que la partie numéro.
        $withoutDevice = strstr($digits, ':', true);
        if (false !== $withoutDevice) {
            $digits = $withoutDevice;
        }

        if (1 !== preg_match('/^\d{'.self::MIN_DIGITS.','.self::MAX_DIGITS.'}$/', $digits)) {
            return null;
        }

        return '+'.$digits;
    }
}
