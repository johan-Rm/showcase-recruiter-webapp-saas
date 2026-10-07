import { displayName } from './criteria'

import type { Translate } from './criteria'
import type { AdminOverflowingSearch } from '#shared/types/admin-search'

/**
 * Une ligne de l'inventaire des recherches qui débordent, prête à afficher.
 *
 * Pur : la traduction est injectée, rien n'est lu d'un contexte. Ce que l'écran reçoit ici est déjà
 * décidé — il ne lui reste qu'à peindre.
 */
export interface OverflowRow {
  id: string
  /** Le nom saisi, ou celui que ses critères composent — jamais vide. */
  title: string
  /** Vrai quand le titre vient des critères : l'écran le signale, une recherche n'a pas de nom. */
  isAutoNamed: boolean
  organizationName: string
  ownerName: string
  matchCount: number
  servedCount: number
  /** Ce que le recruteur ne voit pas — la seule raison d'être de cet écran. */
  hiddenCount: number
  isClosed: boolean
  lastActivityAt: string
}

/**
 * Met une recherche en ligne d'inventaire.
 *
 * **Le titre ne peut pas être vide** : une recherche non renommée n'a pas de nom, ses critères en
 * tiennent lieu — c'est la règle que la carte de recherche applique déjà côté entreprise, et la
 * même fonction la porte ici. Sans critère non plus, l'écran met son propre libellé : `title`
 * revient vide et `isAutoNamed` est vrai, à charge de l'appelant de le nommer.
 *
 * `hiddenCount` est calculé et non servi : c'est l'écart entre ce que le vivier ramène et ce qu'on
 * en donne. Le demander à l'API obligerait à tenir deux définitions d'accord.
 */
export const toOverflowRow = (
  search: AdminOverflowingSearch,
  translate: Translate,
): OverflowRow => {
  const named = '' !== search.name.trim()

  return {
    id: search.id,
    title: displayName({ name: search.name, criteria: search.criteria }, translate),
    isAutoNamed: !named,
    organizationName: search.organizationName,
    ownerName: search.ownerName,
    matchCount: search.matchCount,
    servedCount: search.servedCount,
    hiddenCount: Math.max(0, search.matchCount - search.servedCount),
    isClosed: search.isClosed,
    lastActivityAt: search.lastActivityAt,
  }
}

/**
 * Ce que l'écran montre : la liste, ou l'une des trois raisons de ne pas la montrer.
 *
 * Un seul état à la fois, et l'ORDRE tranche les cas où plusieurs seraient vrais. Le chargement
 * passe devant l'erreur — une nouvelle tentative en cours ne doit pas continuer d'afficher l'échec
 * de la précédente —, et l'erreur passe devant le vide : un inventaire qui n'a pas pu être chargé
 * n'est pas un inventaire sans débordement, et les confondre annoncerait une bonne nouvelle qu'on
 * n'a pas constatée.
 */
export type OverflowScreenState = 'loading' | 'error' | 'empty' | 'list'

export const overflowScreenState = (screen: {
  pending: boolean
  error: boolean
  total: number
}): OverflowScreenState => {
  if (screen.pending) return 'loading'
  if (screen.error) return 'error'

  return 0 === screen.total ? 'empty' : 'list'
}

/**
 * Combien de pages pour un total et une taille de page.
 *
 * **Au moins une**, même sans résultat : une pagination qui annoncerait « page 1 sur 0 » se lit
 * comme une panne. Une taille de page absurde rend une page unique plutôt qu'une division par zéro.
 */
export const pageCount = (total: number, perPage: number): number =>
  perPage > 0 ? Math.max(1, Math.ceil(total / perPage)) : 1
