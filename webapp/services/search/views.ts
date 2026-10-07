import type { CandidateCard } from '../../shared/types/candidate-card'

/**
 * Les deux vues de « Ma sélection » : ce qu'on a retenu, et le jeu entier.
 *
 * Elles filtrent l'AFFICHAGE, jamais le périmètre de la recherche. Il y en avait sept — Nouveaux, Au
 * Maroc, Disponibles, Non dévoilés, Dévoilés — qui découpaient une POPULATION ; cet écran ne regarde
 * plus une population mais une récolte, et sur une liste de dix ou vingt profils il n'y a plus rien
 * à découper. Ces états sont devenus des badges sur le profil, là où ils informent sans structurer.
 *
 * LES RETENUS D'ABORD, parce que c'est l'étape où l'on contacte : le parcours du produit va de
 * *rechercher* à *sélectionner* puis *contacter*, et un écran de contact ouvert sur le vivier entier
 * invite à dépenser un crédit sur un profil que personne n'a regardé.
 *
 * « Tous » reste malgré tout : retenir n'engage à rien, et il faut pouvoir se rattraper. L'entonnoir
 * guide, il n'enferme pas — et c'est cette échappatoire qui oblige le tableau à savoir retenir, faute
 * de quoi elle ne mènerait qu'à un dévoilement.
 */
export const SEARCH_VIEWS = ['favorites', 'all'] as const

export type SearchView = (typeof SEARCH_VIEWS)[number]

const matchers: Record<SearchView, (candidate: CandidateCard) => boolean> = {
  favorites: (candidate) => true === candidate.isFavorite,
  all: () => true,
}

/**
 * Le nom de chaque vue DANS L'ADRESSE.
 *
 * En français, comme l'onglet qu'il désigne : une adresse est lue par la personne qui la partage à
 * un collègue, et `?vue=favorites` mélangerait les deux langues sous ses yeux.
 *
 * LA VUE PAR DÉFAUT N'A PAS DE NOM, et n'a pas à en avoir : elle s'écrit par l'ABSENCE du paramètre.
 * C'est ce qui garde l'adresse courante propre, et ce qui fait que revenir au défaut rend exactement
 * l'adresse d'où l'on venait. Le défaut ayant changé de vue, c'est mécaniquement `all` qui prend un
 * nom et `favorites` qui le perd.
 *
 * Une adresse ancienne portant `?vue=favoris` reste donc juste : ce nom n'est plus reconnu, elle
 * retombe sur le défaut — qui est précisément la vue qu'elle demandait.
 */
const VIEW_SLUGS: Partial<Record<SearchView, string>> = { all: 'tous' }

/** Le nom d'une vue dans l'adresse — `undefined` pour la vue par défaut, qui ne s'écrit pas. */
export const viewSlug = (view: SearchView): string | undefined => VIEW_SLUGS[view]

/**
 * La vue demandée par l'adresse, quoi qu'elle contienne.
 *
 * TOUT ce qui n'est pas reconnu retombe sur le DÉFAUT : une adresse tronquée en la recopiant, un
 * signet qui survit à la suppression d'une vue, ou un tableau si le paramètre est répété
 * (`?vue=tous&vue=favoris`). Aucun de ces cas ne doit produire un écran vide — l'utilisateur n'a
 * alors aucun moyen de comprendre ce qu'il regarde.
 */
export const toSearchView = (raw: unknown): SearchView => {
  const slug = Array.isArray(raw) ? raw.at(0) : raw

  return (
    SEARCH_VIEWS.find((view) => undefined !== VIEW_SLUGS[view] && VIEW_SLUGS[view] === slug) ??
    'favorites'
  )
}

/**
 * Le jeu réduit à une vue, **profils vérifiés en tête**.
 *
 * Ce tri est la seule hiérarchie de la Table, et il n'est pas négociable par colonne : il incarne la
 * promesse du produit (rencontré par showcase-recruiter-webapp-saas, constaté et pas supposé). L'ordre relatif du reste est
 * préservé — celui que l'API a rendu.
 */
export const filterByView = (
  candidates: readonly CandidateCard[],
  view: SearchView,
): CandidateCard[] =>
  candidates
    .filter((candidate) => matchers[view](candidate))
    .sort((left, right) => Number(right.isStar) - Number(left.isStar))
