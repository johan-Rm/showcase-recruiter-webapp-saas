<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * L'endroit du profil où un mot-clé a été trouvé (task API-040).
 *
 * Le recruteur ne juge pas de la même façon selon l'endroit : un terme vu dans l'accroche que le
 * candidat s'écrit ne vaut pas un terme vu dans un intitulé de poste qu'il a réellement occupé. Sans
 * cette clé, l'extrait s'affiche sans qu'on sache de quoi il est l'extrait.
 *
 * TROIS VALEURS, ET PAS UNE DE PLUS : ce sont exactement les endroits que le filtre fouille, et ce
 * sont des champs DÉJÀ SERVIS au recruteur, dévoilé ou non. L'extrait rapporté ne montre donc rien
 * que l'écran ne montre déjà, et c'est ce qui le rend sûr sans règle de masquage.
 *
 * Ajouter ici l'employeur d'une expérience romprait cette garantie : c'est la seule donnée du
 * parcours qui attend le dévoilement, parce qu'elle ré-identifie là où un intitulé qualifie sans
 * nommer (cf. `CandidateCardOutput`).
 */
enum MatchField: string
{
    /** L'accroche que le candidat s'écrit. */
    case HEADLINE = 'HEADLINE';

    /** Une compétence déclarée, telle qu'elle est écrite. */
    case SKILL = 'SKILL';

    /** Un intitulé de poste écrit dans le parcours, tel qu'il est écrit — jamais l'employeur. */
    case JOB_TITLE = 'JOB_TITLE';
}
