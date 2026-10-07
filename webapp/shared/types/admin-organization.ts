/**
 * La fiche d'une entreprise pour l'administration (GET /v1/admin/organizations/{id}).
 *
 * Forme plate écrite à la main en miroir d'`AdminOrganizationDetailOutput` côté API.
 *
 * `subscription` est **absent** quand l'entreprise n'a pas d'abonnement : l'API omet les champs
 * nuls, comme partout ailleurs. L'écran teste donc une valeur, pas la présence d'une clé.
 */
export interface AdminOrganizationSubscription {
  plan: string
  status: string
  currentPeriodEnd?: string | null
}

export interface AdminOrganizationMember {
  id: string
  givenName: string
  familyName: string
  contactEmail?: string | null
  organizationRole: string
  jobTitle?: string | null
  status: string
}

/** Un mouvement de crédits. `balanceAfter` rend le journal auto-portant. */
export interface AdminCreditTransaction {
  id: string
  type: string
  amount: number
  balanceAfter: number
  reason?: string | null
  recruiterId?: string | null
  createdAt: string
}

export interface AdminOrganizationDetail {
  id: string
  name: string
  legalName?: string | null
  country?: string | null
  city?: string | null
  validationStatus: string
  createdAt: string
  creditBalance: number
  subscription?: AdminOrganizationSubscription | null
  members: AdminOrganizationMember[]
  creditTransactions: AdminCreditTransaction[]
}
