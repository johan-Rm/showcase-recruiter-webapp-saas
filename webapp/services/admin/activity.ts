import { pageCount } from '../search/overflow'

import type { AdminActivityEntry } from '#shared/types/admin-activity'

/**
 * Le calcul de pages est le même partout : il est réutilisé plutôt que recopié, et ré-exporté ici
 * pour que l'écran n'ait qu'un service à connaître. Le déplacer dans un service commun toucherait
 * l'écran des recherches, qui n'a rien demandé.
 */
export { pageCount }

/**
 * Une ligne du fil d'activité, prête à afficher.
 *
 * Pur : rien n'est lu d'un contexte. Ce que l'écran reçoit est déjà décidé — il ne lui reste qu'à
 * peindre.
 */
export interface ActivityRow {
  id: string
  viewedAt: string
  recruiterName: string
  /** `null` plutôt qu'absent : l'écran teste une valeur, pas la présence d'une clé. */
  recruiterEmail: string | null
  organizationName: string
  candidateId: string
  candidateName: string
  revealed: boolean
}

/**
 * Met une visite en ligne de fil.
 *
 * **Aucun nom ne peut être vide.** Une trace historique survit à la fermeture d'un compte, et rien
 * ne garantit qu'une personne effacée ait laissé un nom lisible ; l'écran doit pouvoir écrire
 * quelque chose plutôt qu'une case blanche. Le repli est fourni par l'appelant, qui seul connaît la
 * langue.
 */
export const toActivityRow = (entry: AdminActivityEntry, unknownLabel: string): ActivityRow => ({
  id: entry.id,
  viewedAt: entry.viewedAt,
  recruiterName: '' === entry.recruiterName.trim() ? unknownLabel : entry.recruiterName,
  recruiterEmail: entry.recruiterEmail ?? null,
  organizationName: '' === entry.organizationName.trim() ? unknownLabel : entry.organizationName,
  candidateId: entry.candidateId,
  candidateName: '' === entry.candidateName.trim() ? unknownLabel : entry.candidateName,
  revealed: entry.revealed,
})

/**
 * Ce que l'écran montre : le fil, ou l'une des trois raisons de ne pas le montrer.
 *
 * Un seul état à la fois, et l'ORDRE tranche les cas où plusieurs seraient vrais. Le chargement
 * passe devant l'erreur — une nouvelle tentative ne doit pas continuer d'afficher l'échec de la
 * précédente —, et l'erreur passe devant le vide : un fil qui n'a pas pu être chargé n'est pas un
 * fil sans visite, et les confondre annoncerait que personne ne regarde les profils alors qu'on
 * n'en sait rien.
 */
export type ActivityScreenState = 'loading' | 'error' | 'empty' | 'list'

export const activityScreenState = (screen: {
  pending: boolean
  error: boolean
  total: number
}): ActivityScreenState => {
  if (screen.pending) return 'loading'
  if (screen.error) return 'error'

  return 0 === screen.total ? 'empty' : 'list'
}
