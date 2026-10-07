<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

/**
 * Point de normalisation UNIQUE du vocabulaire : récolte, enrichissement et taxonomie doivent voir
 * la même forme d'un terme, sans quoi un même mot compte deux fois. C'est arrivé — `travail
 * d'équipe` et `travail d’équipe` ont vécu comme deux termes distincts, et 860 occurrences
 * échappaient au dictionnaire. Pure et sans état — testable en isolation.
 */
final class TermNormalizer
{
    /**
     * Forme commune à tout terme : entités HTML, apostrophes typographiques, casse et espaces.
     * Ne touche pas à la ponctuation de bord, porteuse de sens sur les compétences
     * (« .net framework »).
     */
    public function normalize(string $raw): string
    {
        $s = mb_strtolower(trim($this->decode($raw)));

        return preg_replace('#\s+#u', ' ', $s) ?? $s;
    }

    /**
     * Réduit un intitulé de poste à sa forme canonique : coupe au premier séparateur porteur de
     * bruit d'entreprise ou de précision (« Product Owner / AMOA | Renewables » → « product
     * owner »), puis normalise et rogne la ponctuation de bord.
     */
    public function canonicalizeTitle(string $raw): string
    {
        $s = $this->decode($raw);
        $s = preg_split('#\s*[|/(·—,]\s*#u', $s, 2)[0] ?? $s;
        $s = preg_split('#\s+-\s+#u', $s, 2)[0] ?? $s;
        $s = preg_split('#\s+I\s+#u', $s, 2)[0] ?? $s;
        $s = preg_split('#\s+(?:chez|at)\s+#ui', $s, 2)[0] ?? $s;

        return $this->trimEdgePunctuation($this->normalize($s));
    }

    /**
     * Rognage en UTF-8 : `trim()` avec liste de caractères travaille par octet et tronquerait les
     * caractères multi-octets voisins (un titre finissant par « ط » perdait son second octet).
     */
    private function trimEdgePunctuation(string $s): string
    {
        $s = preg_replace('#^[\s\-·—,.]+#u', '', $s) ?? $s;

        return preg_replace('#[\s\-·—,.]+$#u', '', $s) ?? $s;
    }

    private function decode(string $raw): string
    {
        return str_replace(['’', '‘'], "'", html_entity_decode($raw, \ENT_QUOTES | \ENT_HTML5));
    }
}
