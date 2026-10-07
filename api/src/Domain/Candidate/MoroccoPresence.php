<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Entity\Candidate;
use DateTimeImmutable;

/**
 * Le candidat est-il physiquement au Maroc **aujourd'hui** ?
 *
 * Le verdict descend désormais avec la carte, au lieu d'être recalculé dans le navigateur. Deux
 * raisons, et la seconde est la vraie.
 *
 * D'abord il DÉPEND DE QUAND ON LE CALCULE : rendu côté client, il suit l'horloge et le fuseau du
 * poste, et une page ouverte depuis la veille affiche un badge périmé. Une date de séjour se compare
 * à un « aujourd'hui » qui doit être celui du serveur, comme le reste du jeu de résultats.
 *
 * Ensuite l'arithmétique de dates n'a pas à exister en deux langages : elle vivait en PHP pour
 * filtrer et en TypeScript pour badger, et deux écritures d'un même calcul finissent par se
 * contredire.
 *
 * ⚠️ CE VERDICT N'EST PAS LE FILTRE. « Au Maroc » dit *présent aujourd'hui* ; le critère de recherche
 * `presenceWithinDays` dit *présent dans les N prochains jours*. Ce sont deux questions différentes
 * sous un même nom, et c'est voulu : un candidat qui arrive dans dix jours entre dans une recherche
 * « présent sous 30 jours » sans être au Maroc à l'instant. Les aligner ferait mentir l'un des deux.
 */
final readonly class MoroccoPresence
{
    /** Bornes INCLUSES : un séjour du 8 au 24 couvre le 8 et le 24. */
    public static function isPresentOn(Candidate $candidate, DateTimeImmutable $day): bool
    {
        $date = $day->format('Y-m-d');

        foreach ($candidate->getPresencePeriods() as $period) {
            if ($period->startDate->format('Y-m-d') <= $date && $date <= $period->endDate->format('Y-m-d')) {
                return true;
            }
        }

        return false;
    }
}
