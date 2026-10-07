<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Candidate\RevealedAccess;
use App\Entity\Candidate;

/**
 * Rend la sélection en CSV (task 077). Le format vise UN usage : ouvrir le fichier dans Excel FR
 * sans manipulation — d'où le séparateur `;` et le BOM UTF-8, sans quoi les accents s'affichent en
 * mojibake.
 *
 * Il masque PAR CANAL (task `API-018`) : depuis que l'e-mail et le téléphone s'achètent séparément,
 * « ce profil est dévoilé » ne veut plus rien dire à soi seul. L'appelant n'admet que les profils
 * dont au moins un canal est payé, et le writer laisse vide la colonne du canal qui ne l'est pas —
 * un export ne doit jamais livrer ce qui n'a pas été acheté.
 */
final class SearchCsvWriter
{
    private const string BOM = "\u{FEFF}";
    private const string SEPARATOR = ';';

    private const array HEADERS = ['Nom', 'Poste', 'E-mail', 'Téléphone', 'Ville', 'Pays', 'Présence Maroc'];

    /**
     * @param list<Candidate>            $candidates
     * @param array<int, RevealedAccess> $access     ce qui est payé, par id interne de candidat
     */
    public function write(array $candidates, array $access): string
    {
        $rows = [self::HEADERS];
        foreach ($candidates as $candidate) {
            $person = $candidate->getPerson();
            $paid = $access[(int) $candidate->getId()] ?? RevealedAccess::none();
            $rows[] = [
                trim($person->givenName.' '.$person->familyName),
                $candidate->getJarvi()->headline ?? '',
                $paid->email ? ($person->contactEmail ?? '') : '',
                $paid->phone ? ($person->phone ?? '') : '',
                $person->city ?? '',
                $person->country ?? '',
                self::presence($candidate),
            ];
        }

        $lines = array_map(
            static fn (array $row): string => implode(self::SEPARATOR, array_map(self::escape(...), $row)),
            $rows,
        );

        return self::BOM.implode("\r\n", $lines)."\r\n";
    }

    /** Nom de fichier lisible : `profils-<recherche>.csv`, l'accentué et les espaces aplatis. */
    public function filename(string $searchName): string
    {
        $slug = mb_strtolower(trim($searchName));
        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        $slug = trim($slug, '-');

        return \sprintf('profils-%s.csv', '' === $slug ? 'sans-nom' : $slug);
    }

    /** Les fenêtres de présence au Maroc, la raison d'être du produit — jamais tronquées. */
    private static function presence(Candidate $candidate): string
    {
        $windows = [];
        foreach ($candidate->getPresencePeriods() as $period) {
            $windows[] = null !== $period->city && '' !== $period->city
                ? \sprintf('%s → %s (%s)', $period->startDate->format('d/m/Y'), $period->endDate->format('d/m/Y'), $period->city)
                : \sprintf('%s → %s', $period->startDate->format('d/m/Y'), $period->endDate->format('d/m/Y'));
        }

        return implode(' · ', $windows);
    }

    /**
     * Échappement CSV : guillemets doublés, champ cité dès qu'il contient un séparateur, un
     * guillemet ou un saut de ligne. Un `;` dans un intitulé de poste ne doit pas décaler la ligne.
     */
    private static function escape(string $value): string
    {
        if (!str_contains($value, self::SEPARATOR) && !str_contains($value, '"') && !str_contains($value, "\n") && !str_contains($value, "\r")) {
            return $value;
        }

        return '"'.str_replace('"', '""', $value).'"';
    }
}
