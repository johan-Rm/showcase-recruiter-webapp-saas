<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\CandidateSearchProvider;
use App\State\Provider\SearchResultsProvider;
use DateTimeImmutable;

/**
 * Carte candidat en recherche (aperçu) — contrat UNIQUE de la Table et de l'inspecteur : une requête
 * peint une page, aucun appel par ligne.
 *
 * Masquage tant que le profil n'est pas dévoilé : prénom et nom réduits à l'initiale, coordonnées
 * `null` (seule leur PRÉSENCE est exposée, via `hasEmail` / `hasPhone`), employeur
 * jamais transmis (cf. CandidateOutput). La présence Maroc ★ reste visible (hook produit) ; les années d'expérience
 * aussi (agrégat, pas une identité). Compétences plafonnées à l'aperçu — la liste complète vit dans
 * /candidates/{id}.
 */
#[ApiResource(
    shortName: 'CandidateSearch',
    operations: [
        new Get(
            uriTemplate: '/search/candidates',
            output: CollectionOutput::class,
            provider: CandidateSearchProvider::class,
        ),
        // Le jeu de résultats d'UNE recherche, servi depuis l'appartenance matérialisée, chaque carte
        // portant son état de travail. Le moteur ci-dessus, lui, ne connaît aucune recherche.
        //
        // Enveloppe propre et non `CollectionOutput` : elle porte en plus la liste COMPLÈTE des
        // identifiants du jeu, que la page seule ne suffit pas à connaître.
        new Get(
            uriTemplate: '/searches/{searchId}/results',
            uriVariables: ['searchId'],
            output: SearchResultsOutput::class,
            provider: SearchResultsProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class CandidateCardOutput
{
    /**
     * @param list<array{startDate: string, endDate: string, city: ?string}> $presencePeriods
     * @param list<string>                                                   $skills
     * @param list<array{language: string, proficiency: ?string}>            $languages
     * @param list<string>                                                   $experienceSectors
     */
    public function __construct(
        public string $id,
        public string $givenName,
        public ?string $familyName,
        /**
         * Photo : URL publique Jarvi servie telle quelle (le front dimensionne).
         *
         * HORS du dévoilement, contrairement aux coordonnées : c'est un choix produit assumé. Une
         * photo identifie pourtant mieux qu'un nom — photo + poste + ville suffisent généralement à
         * retrouver quelqu'un. Le masquage porte donc sur les MOYENS DE CONTACT, pas sur l'anonymat.
         */
        public ?string $photoUrl,
        public ?string $headline,
        /**
         * Poste ACTUEL, dérivé du parcours à l'ingestion (task `117`) — l'intitulé seul, jamais
         * l'employeur, qui lui ne sort qu'au dévoilement.
         *
         * SERVI MÊME MASQUÉ, sans condition d'accès : un intitulé de poste QUALIFIE SANS NOMMER,
         * comme les années d'expérience. C'est l'employeur qui ré-identifie.
         *
         * `null` sur 27 % du vivier — un parcours sans expérience ouverte n'a pas de poste actuel,
         * et ce n'est pas une donnée manquante à combler. À distinguer de `headline`, qui est
         * l'accroche que le candidat s'écrit et qui porte souvent un paragraphe entier.
         */
        public ?string $currentTitle,
        /** Années d'expérience dérivées du parcours à l'ingestion — visibles même masqué (agrégat). */
        public int $experienceYears,
        /**
         * Niveau d'études — sur la carte parce qu'on FILTRE dessus : un critère qu'on ne peut pas
         * vérifier sur le résultat oblige à ouvrir chaque fiche pour contrôler ce qu'on a demandé.
         */
        public ?string $educationLevel,
        public ?string $city,
        public ?string $country,
        /** Coordonnées : la VALEUR n'est servie qu'au profil dévoilé ; sinon `null`. */
        public ?string $contactEmail,
        public ?string $phone,
        /** Joignabilité : la PRÉSENCE d'une coordonnée, exposée même masqué — jamais sa valeur. */
        public bool $hasEmail,
        public bool $hasPhone,
        /**
         * Les deux autres canaux payants disent eux aussi leur présence (task `API-064`) : sans ça,
         * l'écran annonce un coût pour un CV qui n'existe pas, et le serveur refuse ensuite de le
         * facturer — un bouton qui propose puis ne fait rien.
         *
         * Le CV compte s'il porte un FICHIER, le compte rendu s'il a un TEXTE : les règles vivent
         * dans `Domain\Candidate\RevealableContent`, avec celles du dévoilement.
         */
        public bool $hasResume,
        public bool $hasReport,
        /**
         * Dernier contact AVEC le candidat et son canal (task 121) — exposés MÊME masqué : ils
         * disent qu'un échange a eu lieu, jamais l'adresse utilisée (même logique que
         * `hasEmail`/`hasPhone`). C'est avant de dépenser un dévoilement qu'un recruteur a besoin
         * de savoir qu'un confrère est déjà passé.
         *
         * Vide sur ~94 % du vivier, et c'est exact : ces candidats n'ont jamais été approchés.
         * Ne PAS lire comme une fraîcheur de profil.
         */
        public ?DateTimeImmutable $lastInteractionAt,
        public ?string $lastInteractionType,
        public ?string $desiredSector,
        public ?string $desiredFunction,
        public ?string $availability,
        public ?string $returnHorizon,
        /**
         * Le projet de retour DANS LES MOTS DU CANDIDAT.
         *
         * Sur la carte et plus seulement sur la fiche : c'est ce qui sépare « il cherche un poste »
         * de « il veut rentrer », et donc l'élément le plus décisif quand on trie des profils un par
         * un. Le reste du bloc Maroc dit des faits ; celui-ci dit une intention.
         */
        public ?string $returnProject,
        public bool $geoMobility,
        public bool $hasPiedATerre,
        public ?string $piedATerreCity,
        public array $presencePeriods,
        /**
         * Présent au Maroc **aujourd'hui** — le verdict, pas les fenêtres.
         *
         * Calculé ici et plus dans le navigateur : il dépend de « quand », et l'horloge qui compte
         * est celle du serveur, pas celle du poste. Une page ouverte depuis la veille affichait
         * sinon un badge périmé.
         *
         * À ne pas confondre avec le critère `presenceWithinDays`, qui dit *présent dans les N
         * prochains jours* : deux questions différentes sous un même nom.
         */
        public bool $isInMorocco,
        public array $skills,
        /**
         * Les langues parlées, avec leur niveau — celui du parcours, jamais supposé.
         *
         * ⚠️ `proficiency` est `null` sur une part du vivier, et l'absence ne dit pas « débutant » :
         * la langue maternelle arrive souvent sans niveau depuis le réseau d'origine. Un
         * consommateur qui traiterait `null` comme un plancher classerait ces profils au plus bas
         * de ce qu'ils maîtrisent le mieux.
         */
        public array $languages,
        /**
         * TOUS les secteurs exercés, pas le principal.
         *
         * Un candidat passé par la santé puis la banque doit rester trouvable sur « santé » : c'est
         * chez les profils qui ont bifurqué que la question se pose, et n'en servir qu'un les
         * rendrait invisibles sur la moitié de leur parcours.
         */
        public array $experienceSectors,
        public bool $isStar,
        /**
         * Ce que l'entreprise a PAYÉ sur ce profil — quatre accès indépendants (task `API-018`).
         *
         * `revealedEmail` et `revealedPhone` commandent chacun SON canal, et rien d'autre :
         * l'identité, l'employeur et le profil LinkedIn se servent sans crédit. `revealedResume`
         * ouvre le CV, `revealedReport` le compte rendu d'entretien, tous deux servis sur la fiche.
         */
        public bool $revealedEmail,
        public bool $revealedPhone,
        public bool $revealedResume,
        public bool $revealedReport,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        /**
         * État de travail du candidat DANS une recherche — `null` hors d'une recherche.
         *
         * Ils ne décrivent pas le candidat mais la place qu'il occupe dans le plan de travail d'une
         * recherche : `null` sur /search/candidates, qui interroge le moteur sans recherche, et
         * renseignés sur /searches/{searchId}/results, qui sert le jeu de l'une d'elles. Un booléen
         * y répondrait « non » là où la question ne se pose pas.
         *
         * `isNew` dit l'ÂGE du profil dans le vivier — arrivé il y a moins de sept jours — et non
         * ce que le recruteur a déjà ouvert : deux membres d'une même recherche voient donc les
         * mêmes nouveautés, comme ils voient les mêmes favoris. Ne pas le confondre avec le
         * `newCount` d'une recherche, qui compte, lui, ce qui a bougé depuis la dernière visite de
         * CE recruteur.
         */
        public ?bool $isNew = null,
        public ?bool $isFavorite = null,
        /**
         * Où EN EST LE RECRUTEUR COURANT sur ce profil — `null` hors d'une recherche.
         *
         * Personnels tous les deux, contrairement au favori : mon avancement n'est pas celui de mon
         * collègue, et écarter est un jugement relatif à un besoin. Cf. `SearchProfileReview`.
         */
        public ?bool $isSeen = null,
        public ?bool $isDismissed = null,
        /**
         * Pourquoi ce profil est dans le jeu : chaque critère de la recherche, satisfait ou non, et
         * par quoi. `null` hors d'une recherche, comme l'état de travail ci-dessus — sans critères,
         * la question n'a pas d'objet.
         *
         * Ce que ça permet, et qu'aucune autre donnée ne permettait : surligner les mots-clés
         * trouvés, énoncer la correspondance de poste que le dictionnaire a résolue, et lister ce
         * qui MANQUE — le seul moyen de trancher quand un profil entre en ne satisfaisant qu'un
         * critère sur quatre.
         *
         * @var list<CriterionMatchOutput>|null
         */
        public ?array $matches = null,
    ) {
    }
}
