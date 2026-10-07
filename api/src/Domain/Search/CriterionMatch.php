<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Enum\JobTenure;
use App\Enum\MatchField;

/**
 * Ce qu'un critère de recherche donne SUR UN PROFIL : satisfait ou non, et par quoi.
 *
 * Le « par quoi » n'est pas un ornement. Le critère d'intitulé de poste passe par le dictionnaire de
 * concepts : chercher « développeur » retrouve un profil dont le poste s'affiche « Frontend
 * developer ». Sans le libellé DU PROFIL, l'appelant ne peut ni surligner (il n'y a pas la chaîne
 * cherchée dans la fiche) ni expliquer la correspondance — et le travail du dictionnaire reste
 * invisible, alors qu'il est ce qui a ramené le profil.
 *
 * `matchedOn` reste `null` quand le critère n'est pas satisfait, et quand le satisfaire ne révèle
 * rien de plus que le critère lui-même (un booléen).
 *
 * `matchedWhen` se lit en paire avec lui : l'un dit **par quoi** le critère est satisfait, l'autre
 * **quand**. Seul le métier a un « quand » qui se calcule — une langue ou un pays n'en ont pas.
 */
final readonly class CriterionMatch
{
    public function __construct(
        public string $facet,
        public string $value,
        public bool $matched,
        /** Le libellé DU PROFIL qui satisfait le critère — jamais celui qui a été cherché. */
        public ?string $matchedOn = null,
        /**
         * Ce métier est-il encore exercé — `null` quand on ne le sait pas.
         *
         * Le poste du moment n'est résolu que sur la moitié du vivier, et il est parfois marqué
         * ambigu. Dans ces cas, la correspondance se tait : rien ne s'affiche, rien n'est affirmé.
         */
        public ?JobTenure $matchedWhen = null,
        /**
         * OÙ, dans le profil, le terme a été vu — `null` quand la facette n'a qu'un endroit possible.
         *
         * Seuls les mots-clés en ont besoin : ce sont les seuls à fouiller plusieurs endroits, et
         * l'extrait seul ne dit pas s'il vient d'une accroche que le candidat s'écrit ou d'un poste
         * qu'il a réellement occupé. Les deux ne valent pas la même chose pour qui recrute.
         */
        public ?MatchField $matchedIn = null,
    ) {
    }
}
