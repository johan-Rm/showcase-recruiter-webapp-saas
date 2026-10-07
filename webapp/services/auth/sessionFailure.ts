import { readErrorString } from './errorReason'

/**
 * Le motif que le BFF joint à son refus quand la session ne peut plus servir : le jeton est rejeté
 * par l'API et le refresh n'a rien pu y faire.
 *
 * Le code 401 seul ne suffirait pas à conclure. Un appel mal formé, un endpoint qui refuse pour
 * une autre raison, une passerelle qui répond à la place du BFF portent le même code — s'y fier
 * déconnecterait quelqu'un dont la session est parfaitement vivante. Seul le motif nommé le dit.
 */
export const SESSION_EXPIRED = 'session_expired'

/**
 * La session est-elle définitivement close ?
 *
 * Lu côté navigateur, sur l'erreur que le BFF vient de composer : `createError({ data })` range son
 * contenu sous `data.data`. Tout ce qui n'est pas explicitement ce motif — panne réseau, refus de
 * droits, erreur serveur — répond non : on ne devine pas un motif qu'on n'a pas lu.
 */
export const isSessionExpired = (cause: unknown): boolean =>
  SESSION_EXPIRED === readErrorString(cause, ['data', 'data', 'reason'])
