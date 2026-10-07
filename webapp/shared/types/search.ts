/**
 * Recherche du cockpit entreprise (GET /v1/searches). L'enveloppe générée
 * (`Search.CollectionOutput`) type ses `items` en opaque et dégrade `criteria` en
 * `(string | null)[]` — l'OpenAPI ne porte ni le paramètre de type de la collection, ni la forme
 * des critères. On rétablit ici la forme réelle, une seule fois, en miroir de `SavedSearchOutput`
 * côté API.
 */

import type { CandidateCard, CriterionMatch } from './candidate-card'

/**
 * Une proposition d'auto-complétion : le terme, et rien d'autre.
 *
 * PAS DE NOMBRE DE PROFILS à côté du terme, ni ici ni dans la réponse : il décrirait le vivier
 * avant qu'on l'ait acheté — sa taille, sa composition — à qui tape trois lettres au hasard.
 *
 * L'ORDRE, LUI, RESTE SIGNIFIANT : le serveur classe toujours de la proposition la plus portée à la
 * moins portée, et n'en sert aucune que personne n'exerce.
 */
export interface SearchSuggestion {
  value: string
}

export interface SearchSuggestionCollection {
  items: SearchSuggestion[]
  total: number
  page: number
  perPage: number
}

/** Un critère posé : sa facette, sa valeur, et le groupe qui le nomme dans le nom auto de la recherche. */
export interface SearchCriterion {
  facet: string
  value: string
  group: string
}

export interface Search {
  id: string
  /** Vide tant qu'aucun critère n'est posé : une recherche sans critère n'a pas de nom. */
  name: string
  criteria: SearchCriterion[]
  /** Faux dès qu'un nom a été saisi à la main ; vider le champ réactive l'auto-nommage. */
  isAutoNamed: boolean
  isShared: boolean
  ownerId: string
  ownerName: string
  /** Faux sur la recherche d'un collègue : elle est alors en LECTURE SEULE (un seul auteur, pas de co-édition). */
  isOwn: boolean
  /** Profils ayant rejoint le jeu de résultats depuis la dernière ouverture — propre au recruteur courant. */
  newCount: number
  /**
   * Taille du jeu de résultats : le « 34 » de la carte. Lue sur la même appartenance matérialisée que
   * celle servie par la Table — la carte annonce donc exactement ce que la Table montrera. Vaut 0 tant
   * qu'aucun critère n'est posé.
   */
  resultCount: number
  /**
   * Où en est le recruteur COURANT dans cette pile — comme `newCount`, et pour la même raison :
   * deux recruteurs sur une recherche partagée n'ont pas le même avancement.
   *
   * BORNÉ au jeu de résultats par l'API : un profil qui cesse de correspondre quitte ce compte,
   * alors qu'il reste dans `favoriteCount`.
   */
  seenCount: number
  /** Ce que la recherche a produit — tous les retenus, y compris ceux sortis du jeu. */
  favoriteCount: number
  /**
   * Quand son auteur a déclaré le travail terminé. Nul tant qu'elle est active.
   *
   * Une valeur nulle n'est pas sérialisée par l'API : la clé est alors ABSENTE de la réponse, d'où
   * l'optionnel — c'est le comportement de tout le contrat, pas une particularité de ce champ.
   */
  closedAt?: string | null
  createdAt: string
  updatedAt: string
}

export interface SearchCollection {
  items: Search[]
  total: number
  page: number
  perPage: number
}

/**
 * Le jeu de résultats d'une recherche (GET /v1/searches/{id}/results).
 *
 * `items` est une PAGE ; `resultIds` porte le jeu ENTIER, dans le même ordre. C'est ce qui permet de
 * compter, de cocher, et de savoir ce qu'il reste à charger sans avoir tout téléchargé.
 */
export interface SearchResults {
  items: CandidateCard[]
  total: number
  page: number
  perPage: number
  resultIds: string[]
  /**
   * L'ÉTAT DE TRAVAIL DE LA RECHERCHE ENTIÈRE, servi avec l'ouverture — et avec elle seulement.
   *
   * Facultatifs parce que l'API ne les sert qu'à la première page de la pile : les périmètres
   * `?scope=` sont demandés ensuite, quand le client connaît déjà cet état. **Absents plutôt que
   * vides** — un tableau vide y signifierait « aucun retenu », ce qui serait faux.
   *
   * Ils portent sur la recherche entière et non sur les cartes servies : un profil retenu au-delà du
   * jeu chargé serait sinon compté sans être connu.
   */
  favoriteIds?: string[]
  dismissedIds?: string[]
  seenIds?: string[]
}

/**
 * Les profils retenus dans une recherche (GET /v1/searches/{id}/favorites).
 *
 * Servis à part du jeu de résultats et SANS pagination : `/results` sert une page, et un favori qui
 * n'y figure pas resterait inconnu — l'onglet compterait alors ce qu'on a chargé au lieu de ce qu'on
 * a retenu.
 *
 * Rétabli ici pour la même raison que le reste de ce fichier : l'enveloppe générée type ses `items`
 * en `(string | null)[]`, l'OpenAPI ne portant pas le paramètre de type de la collection.
 */
export interface SearchFavorite {
  id: string
  candidateId: string
  addedById: string
  createdAt: string
}

export interface SearchFavoriteCollection {
  items: SearchFavorite[]
  total: number
  page: number
  perPage: number
}

/** Ce que renvoient la création et l'ajustement d'une recherche : la recherche ET ce qu'elle ramène. */
export interface SearchWithResults {
  search: Search
  results: SearchResults
}

/**
 * La recherche telle qu'elle vit dans le store : son auteur est une RÉFÉRENCE, pas une copie.
 *
 * `ownerName` voyage sur chaque recherche renvoyée par l'API — dix recherches du même collègue, dix
 * fois son nom. Recopié dans le store, il faudrait le corriger en dix endroits le jour où il change.
 * Le nom vit donc une seule fois, dans le store des personnes, et `ownerId` y renvoie.
 */
export type StoredSearch = Omit<Search, 'ownerName'>

/**
 * L'état de travail D'UNE recherche : qui appartient à son jeu, ce qui y est retenu, ce qui y est
 * nouveau, ce qui y est coché.
 *
 * Ces quatre listes sont des RELATIONS (recherche × candidat) et non des attributs du candidat : le
 * même profil peut être favori dans une recherche et pas dans l'autre. Les poser sur la carte —
 * comme le fait la réponse de l'API, qui sert une recherche à la fois — les mélangerait dès que deux
 * recherches partagent un profil.
 *
 * Des identifiants et non des cartes : ces listes doivent survivre à des cartes pas encore chargées,
 * ou sorties de l'affichage. Des tableaux et non des `Set` : le state d'un store reste sérialisable
 * pour le SSR.
 */
export interface SearchSet {
  /** Le jeu complet, dans l'ordre servi par l'API (vérifiés en tête). */
  resultIds: string[]
  favoriteIds: string[]
  /**
   * Les retenus DANS L'ORDRE où l'API les rend, et servis à part du jeu paginé.
   *
   * Ici et non dans un `ref` de composable, comme ses deux voisines : seul le store traverse le
   * rendu serveur. Gardée en local, la liste était calculée au SSR puis perdue à l'hydratation —
   * l'écran affichait la bonne liste, puis zéro.
   */
  retainedIds: string[]
  /** Propre au recruteur courant : deux membres n'ont pas les mêmes nouveautés sur une recherche partagée. */
  newIds: string[]
  /**
   * Où EN EST le recruteur courant dans son tri — personnels tous les deux, contrairement aux
   * favoris.
   *
   * Favoriser est ADDITIF, écarter est SOUSTRACTIF : un écart partagé ferait disparaître de la pile
   * d'un collègue un profil qu'il n'a jamais choisi d'ignorer.
   */
  seenIds: string[]
  dismissedIds: string[]
  selectedIds: string[]
  /**
   * POURQUOI chaque profil répond à CETTE recherche — indexé par identifiant de profil.
   *
   * Une relation comme ses voisines, et pour une raison plus forte encore : le même profil répond à
   * trois recherches pour trois motifs différents. Portée par la carte, l'explication de la dernière
   * recherche ouverte écrasait celle des autres, le pool étant commun.
   *
   * Vide plutôt qu'absente quand rien n'explique un profil : l'affichage la recolle au dernier
   * moment, et une entrée manquante s'y lit comme « pas de critère satisfait », ce qui est vrai.
   */
  matchesById: Record<string, CriterionMatch[]>
}

/**
 * LE PLAN DE TRAVAIL : toutes les recherches ouvertes du recruteur, en une réponse.
 *
 * Le découpage est déjà celui des stores — des fiches dédoublonnées d'un côté, des listes
 * d'identifiants par recherche de l'autre —, si bien qu'il n'y a rien à traduire au-delà du
 * rangement. Un profil qui sert trois recherches n'y voyage qu'une fois.
 */
export interface SearchWorkspaceEntry {
  id: string
  resultIds: string[]
  favoriteIds: string[]
  dismissedIds: string[]
  seenIds: string[]
  /** Les profils récemment arrivés — un fait daté du profil, servi et jamais déduit des fiches. */
  newIds: string[]
}

export interface SearchWorkspace {
  searches: SearchWorkspaceEntry[]
}

/**
 * Les fiches demandées par lot, depuis des recherches nommées
 * (GET /v1/searches/profiles?ids=…&take=…).
 *
 * Ni ordre, ni appartenance, ni explication : l'ordre vient de l'état de travail, et ces fiches vont
 * dans la liste commune qui ne connaît aucune recherche. Un profil qui sert trois des recherches
 * demandées n'y figure qu'une fois.
 */
export interface SearchProfiles {
  items: CandidateCard[]
}
