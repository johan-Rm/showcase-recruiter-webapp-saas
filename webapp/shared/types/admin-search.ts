import type { SearchCriterion } from './search'

/**
 * Une recherche dont le vivier ramène plus de profils que ce qui est servi
 * (GET /v1/admin/searches).
 *
 * Forme plate écrite à la main en miroir d'`AdminOverflowingSearchOutput` côté API : l'enveloppe
 * générée type ses `items` en opaque, l'OpenAPI ne portant pas le paramètre de type d'une
 * collection.
 *
 * `matchCount` et `servedCount` disent deux choses différentes et c'est tout l'intérêt de cet
 * écran : le premier est le nombre RÉEL de correspondances, le second ce que le recruteur peut
 * travailler. Leur écart distingue une recherche trop large d'une demande que le vivier couvre
 * largement.
 */
export interface AdminOverflowingSearch {
  id: string
  /** Le nom SAISI, souvent vide — une recherche non renommée se nomme par ses critères. */
  name: string
  criteria: SearchCriterion[]
  organizationName: string
  ownerName: string
  matchCount: number
  servedCount: number
  isClosed: boolean
  lastActivityAt: string
}

export interface AdminOverflowingSearchCollection {
  items: AdminOverflowingSearch[]
  total: number
  page: number
  perPage: number
}
