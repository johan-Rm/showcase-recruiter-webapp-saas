/**
 * Le dévoilement et son prix.
 *
 * Rétabli ici pour la même raison que les autres types de ce dossier : l'enveloppe générée type les
 * `items` d'une collection en opaque, l'OpenAPI ne portant pas le paramètre de type.
 */

/**
 * Les quatre accès qu'on peut acheter sur un profil.
 *
 * Ils ne se cumulent pas en un forfait : chacun s'achète pour lui-même, et le recruteur ne paie pas
 * un CV dont il n'a pas besoin pour obtenir un numéro — ni l'e-mail pour obtenir le téléphone.
 *
 * L'IDENTITÉ N'EST PAS ICI : le nom, la photo, l'employeur et le profil LinkedIn
 * se lisent sans crédit. On paie pour ATTEINDRE quelqu'un, plus pour savoir qui il est.
 */
export type RevealType = 'EMAIL' | 'PHONE' | 'RESUME' | 'INTERVIEW_REPORT'

export const REVEAL_TYPES: readonly RevealType[] = ['EMAIL', 'PHONE', 'RESUME', 'INTERVIEW_REPORT']

export interface CreditWallet {
  organizationId: string
  /** En crédits, pas en euros : c'est l'unité que l'écran manipule et que l'API débite. */
  balance: number
  updatedAt: string
}

/** Ce qu'un dévoilement rend : la trace de l'accès obtenu. */
export interface RevealedProfile {
  id: string
  candidateId: string
  revealedById: string
  type: RevealType
  revealedAt: string
}

/**
 * Un mouvement du journal de crédits.
 *
 * `balanceAfter` est la colonne qui rend un relevé lisible : sans elle, on additionne de tête pour
 * vérifier où en est le solde.
 */
export interface CreditTransaction {
  id: string
  type: 'GRANT_FREE' | 'PURCHASE' | 'CONSUMPTION' | 'REFUND' | 'ADJUSTMENT'
  amount: number
  balanceAfter: number
  reason: string | null
  recruiterId: string | null
  createdAt: string
}

export interface CreditTransactionCollection {
  items: CreditTransaction[]
  total: number
  page: number
  perPage: number
}
