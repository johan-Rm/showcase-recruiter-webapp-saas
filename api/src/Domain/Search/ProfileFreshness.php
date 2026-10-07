<?php

declare(strict_types=1);

namespace App\Domain\Search;

use DateTimeImmutable;

/**
 * Depuis quand un profil est-il NOUVEAU — un fait daté, pas un repère de lecture.
 *
 * La règle précédente comparait l'entrée du profil dans une recherche à la dernière ouverture du
 * recruteur. Elle disait donc « tu ne l'as pas encore vu », ce que l'écran comptait déjà par
 * ailleurs, et elle s'éteignait au simple rechargement — les résultats servis remettent le repère à
 * jour, sans qu'aucun profil ait été regardé.
 *
 * Ce qu'on veut dire est plus simple : ce profil vient d'arriver. Un fait qui appartient au profil,
 * identique pour tous les membres de l'organisation, et qui s'éteint en VIEILLISSANT plutôt qu'en
 * étant consulté. C'est ce qui le rend partageable : deux collègues voient les mêmes nouveautés et
 * peuvent en parler.
 */
final class ProfileFreshness
{
    /**
     * Sept jours, et le seuil est absolu.
     *
     * Le rythme d'une veille est hebdomadaire : est nouveau ce qu'on n'a pas pu voir la semaine
     * dernière. Un mois en aurait fait un signal presque permanent sur un vivier qui bouge peu — et
     * un badge que presque tous portent ne distingue plus rien.
     */
    public const int NEW_FOR_DAYS = 7;

    /**
     * La borne est STRICTE : un profil arrivé il y a exactement sept jours n'est plus nouveau.
     *
     * `$at` est injecté et jamais lu de l'horloge ici : sans lui, vérifier le huitième jour
     * demanderait d'attendre huit jours. Même motif que `CriterionMatcher::match`.
     */
    public static function isNew(DateTimeImmutable $enteredAt, DateTimeImmutable $at): bool
    {
        return $enteredAt > $at->modify(\sprintf('-%d days', self::NEW_FOR_DAYS));
    }
}
