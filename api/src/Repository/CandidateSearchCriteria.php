<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Availability;
use App\Enum\EducationLevel;
use App\Enum\LanguageProficiency;
use App\Enum\ReturnHorizon;
use App\Enum\Sector;

/**
 * Critères de recherche de candidats (filtres simples V1). Construit par le State Provider
 * depuis la query string, consommé par CandidateRepository::search.
 *
 * `presenceWithinDays` / `presenceCity` ★ ciblent les périodes de présence Maroc (fenêtre
 * [aujourd'hui, +N jours] et/ou ville). Pas de filtre salaire : les prétentions sont stockées
 * multi-devises (EUR/MAD), une comparaison brute des montants serait fausse.
 */
final readonly class CandidateSearchCriteria
{
    public function __construct(
        /**
         * MOTS-CLÉS, reliés en OU entre eux : un profil entre s'il en satisfait **un seul**.
         *
         * Ils cherchent dans le titre ET dans les compétences — le résumé étant vide sur la quasi
         * totalité du vivier, « l'ensemble du profil » se réduit en pratique à ces deux-là.
         *
         * Le OU ne vaut qu'ENTRE LES MOTS-CLÉS : les autres critères continuent de restreindre.
         *
         * @var list<string>
         */
        public array $keywords = [],
        public ?string $sector = null,
        /** Secteur d'EXPÉRIENCE (parcours), à ne pas confondre avec `sector` = secteur RECHERCHÉ. */
        public ?Sector $experienceSector = null,
        /**
         * MÉTIER EXERCÉ au cours du parcours (libellé canonique d'un concept). Présence seule : la
         * durée associée sous-estime, le rattachement des intitulés étant partiel (task 095).
         */
        public ?string $job = null,
        /**
         * ANNÉES D'EXPÉRIENCE MINIMUM. Borne basse seulement : on cherche « au moins tant
         * d'années », jamais « pas plus de ».
         *
         * `null` ou `0` ne restreint rien — c'est le « peu importe » du curseur, qui n'a pas d'état
         * vide naturel. Un profil dont l'expérience n'a pas pu être dérivée porte `0` et sort donc
         * dès qu'un minimum est exigé : on ne lui prête pas une ancienneté qu'on ignore, même règle
         * que le niveau d'études.
         */
        public ?int $experienceMin = null,
        /** Langue parlée (code ISO court). Le niveau ci-dessous ne s'applique qu'avec elle. */
        public ?string $language = null,
        /** Niveau MINIMUM exigé sur `language` : exclut les entrées sans niveau renseigné. */
        public ?LanguageProficiency $languageLevel = null,
        public ?string $function = null,
        public ?Availability $availability = null,
        /** Niveau d'études MINIMUM exigé (task 112/123) : exclut les profils sans niveau reconnu. */
        public ?EducationLevel $educationLevel = null,
        public ?ReturnHorizon $returnHorizon = null,
        public ?bool $geoMobility = null,
        public ?bool $hasPiedATerre = null,
        public ?bool $star = null,
        public ?string $country = null,
        public ?int $presenceWithinDays = null,
        public ?string $presenceCity = null,
        public int $page = 1,
        public int $perPage = 30,
    ) {
    }
}
