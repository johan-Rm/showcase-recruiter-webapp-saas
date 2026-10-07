<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Pourquoi une qualification dérivée manque sur un profil (task API-034).
 *
 * Chaque motif est le miroir d'une branche du calcul qui a rendu zéro ou null — jamais une
 * catégorie inventée après coup. Le rapprochement se lit dans
 * {@see \App\Domain\Reporting\QualificationGapClassifier}, qui est écrit contre les calculateurs
 * eux-mêmes.
 *
 * **Tous les motifs ne sont pas des défauts.** `UNDER_ONE_YEAR` et `UNDER_ONE_MONTH` décrivent des
 * profils que la chaîne a correctement traités : c'est la distinction que cet enum existe pour
 * rendre lisible, et sans elle le compteur du manque surestime le travail à faire.
 *
 * Codes techniques, jamais de phrase : l'interface fournit les mots, dans sa langue.
 */
enum QualificationGapReason: string
{
    /** Ancienneté — aucun poste dans le parcours. */
    case NO_POSITION = 'NO_POSITION';

    /** Ancienneté — des postes, mais aucune date de début lisible et passée. */
    case NO_USABLE_START_DATE = 'NO_USABLE_START_DATE';

    /** Ancienneté — des postes datés, mais toute période retenue finit avant de commencer. */
    case INCOHERENT_DATES = 'INCOHERENT_DATES';

    /** Ancienneté — période mesurable, cumul sous les douze mois. Pas un défaut. */
    case UNDER_ONE_YEAR = 'UNDER_ONE_YEAR';

    /** Poste actuel — aucun poste ouvert : la certitude vaut ABSENT. */
    case NO_ONGOING_POSITION = 'NO_ONGOING_POSITION';

    /** Poste actuel — un poste ouvert, mais sa date de début est absente, illisible ou future. */
    case ONGOING_WITHOUT_START_DATE = 'ONGOING_WITHOUT_START_DATE';

    /** Poste actuel — commencé il y a moins d'un mois plein. Pas un défaut. */
    case UNDER_ONE_MONTH = 'UNDER_ONE_MONTH';

    /** Niveau d'études — aucune formation dans le parcours. */
    case NO_EDUCATION = 'NO_EDUCATION';

    /** Niveau d'études — des formations, aucun intitulé que le référentiel sache lire. */
    case UNRECOGNIZED_TITLE = 'UNRECOGNIZED_TITLE';

    /** Date de modification — la source ne l'a pas fournie. */
    case SOURCE_WITHOUT_DATE = 'SOURCE_WITHOUT_DATE';
}
