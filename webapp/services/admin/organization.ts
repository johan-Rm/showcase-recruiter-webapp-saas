import type {
  AdminCreditTransaction,
  AdminOrganizationDetail,
} from '#shared/types/admin-organization'

/**
 * Une ligne du journal de crédits, prête à afficher.
 *
 * Pur : rien n'est lu d'un contexte.
 */
export interface CreditMovementRow {
  id: string
  createdAt: string
  /** Le type tel que l'API le nomme — l'écran le traduit par une clé i18n. */
  type: string
  /** Montant signé, déjà mis en forme : « +2 », « −1 ». */
  amount: string
  /** Vrai quand des crédits entrent — l'écran s'en sert pour colorer. */
  isCredit: boolean
  balanceAfter: number
  reason: string | null
}

/**
 * Met un mouvement en ligne de journal.
 *
 * Le signe est porté par le texte plutôt que déduit à l'affichage : « −1 » et « +2 » se lisent sans
 * calcul, et le signe moins est le vrai caractère typographique, pas un trait d'union.
 */
export const toCreditMovementRow = (transaction: AdminCreditTransaction): CreditMovementRow => ({
  id: transaction.id,
  createdAt: transaction.createdAt,
  type: transaction.type,
  amount: transaction.amount >= 0 ? `+${transaction.amount}` : `−${Math.abs(transaction.amount)}`,
  isCredit: transaction.amount >= 0,
  balanceAfter: transaction.balanceAfter,
  reason: transaction.reason ?? null,
})

/**
 * Le nom complet d'un membre, jamais vide.
 *
 * Le repli est fourni par l'appelant, qui seul connaît la langue.
 */
export const memberName = (
  member: { givenName: string; familyName: string },
  unknownLabel: string,
): string => {
  const name = `${member.givenName} ${member.familyName}`.trim()

  return '' === name ? unknownLabel : name
}

/**
 * Ce que l'écran montre : la fiche, ou l'une des trois raisons de ne pas la montrer.
 *
 * `notFound` est distinct d'`error` : un identifiant inconnu n'est pas une panne, et proposer de
 * réessayer sur une entreprise qui n'existe pas ferait tourner en rond.
 */
export type OrganizationScreenState = 'loading' | 'notFound' | 'error' | 'sheet'

export const organizationScreenState = (screen: {
  pending: boolean
  notFound: boolean
  error: boolean
  loaded: boolean
}): OrganizationScreenState => {
  if (screen.pending) return 'loading'
  if (screen.notFound) return 'notFound'
  if (screen.error || !screen.loaded) return 'error'

  return 'sheet'
}

/**
 * L'abonnement, ou `null` quand l'entreprise n'en a pas.
 *
 * L'API **omet** le champ plutôt que de le mettre à nul : sans cette normalisation, l'écran devrait
 * tester la présence d'une clé, ce qu'un gabarit fait mal.
 */
export const subscriptionOf = (
  organization: AdminOrganizationDetail,
): AdminOrganizationDetail['subscription'] => organization.subscription ?? null
