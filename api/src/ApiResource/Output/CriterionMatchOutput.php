<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Un critère de la recherche, vu depuis UN profil : satisfait ou non, et par quoi.
 *
 * `facet` et `value` reprennent le critère tel qu'il est stocké — donc tel que l'écran l'affiche en
 * puce. L'appelant peut ainsi rapprocher la correspondance de la puce sans rien recalculer.
 *
 * `matchedOn` porte le libellé **du profil**, jamais celui qui a été cherché. C'est ce qui permet
 * d'énoncer « votre critère *développeur* correspond à son poste *Frontend developer* » — le terme
 * cherché n'apparaît nulle part dans la fiche, il a été résolu par le dictionnaire de concepts.
 * Il reste vide quand le critère n'est pas satisfait, ou quand le satisfaire ne dit rien de plus que
 * le critère lui-même (un booléen).
 *
 * `matchedWhen` se lit en paire avec lui : l'un dit **par quoi** le critère est satisfait, l'autre
 * **quand**. `CURRENT` = ce métier est celui du moment, `PAST` = le profil en occupe un autre
 * aujourd'hui. Absent quand on ne le sait pas — le poste du moment n'est résolu que sur la moitié du
 * vivier, et il est parfois ambigu. **L'absence ne veut pas dire « passé »** : elle veut dire qu'il
 * n'y a rien à afficher.
 */
final class CriterionMatchOutput
{
    public function __construct(
        public string $facet,
        public string $value,
        public bool $matched,
        public ?string $matchedOn,
        public ?string $matchedWhen = null,
        /**
         * OÙ, dans le profil, un mot-clé a été vu — `null` pour toutes les autres facettes, qui
         * n'ont qu'un endroit possible.
         *
         * L'extrait seul ne dit pas de quoi il est l'extrait : un terme vu dans l'accroche que le
         * candidat s'écrit ne vaut pas le même crédit qu'un terme vu dans un poste qu'il a occupé.
         */
        public ?string $matchedIn = null,
    ) {
    }
}
