import { describe, expect, it } from 'vitest'

import {
  memberName,
  organizationScreenState,
  subscriptionOf,
  toCreditMovementRow,
} from './organization'

import type {
  AdminCreditTransaction,
  AdminOrganizationDetail,
} from '../../shared/types/admin-organization'

const movement = (overrides: Partial<AdminCreditTransaction> = {}): AdminCreditTransaction => ({
  id: 'm1',
  type: 'CONSUMPTION',
  amount: -1,
  balanceAfter: 1,
  reason: 'Dévoilement — EMAIL',
  createdAt: '2026-08-19T06:44:00+00:00',
  ...overrides,
})

const organization = (
  overrides: Partial<AdminOrganizationDetail> = {},
): AdminOrganizationDetail => ({
  id: 'o1',
  name: 'showcase-recruiter-webapp-saas Demo SARL',
  validationStatus: 'VALIDEE',
  createdAt: '2026-08-02T13:15:40+00:00',
  creditBalance: 2,
  members: [],
  creditTransactions: [],
  ...overrides,
})

describe('toCreditMovementRow', () => {
  /** « −1 » et « +2 » se lisent sans calcul ; le signe moins est le vrai caractère, pas un tiret. */
  it('met le signe dans le texte du montant', () => {
    expect(toCreditMovementRow(movement({ amount: -1 })).amount).toBe('−1')
    expect(toCreditMovementRow(movement({ amount: 2 })).amount).toBe('+2')
    expect(toCreditMovementRow(movement({ amount: 0 })).amount).toBe('+0')
  })

  it('dit si des crédits entrent, pour que l écran colore', () => {
    expect(toCreditMovementRow(movement({ amount: 5 })).isCredit).toBe(true)
    expect(toCreditMovementRow(movement({ amount: -5 })).isCredit).toBe(false)
  })

  /** C'est `balanceAfter` qui rend le journal auto-portant : il ne se recalcule pas à l'affichage. */
  it('reporte le solde après opération tel quel', () => {
    expect(toCreditMovementRow(movement({ balanceAfter: 7 })).balanceAfter).toBe(7)
  })

  it('rend un motif absent comme nul', () => {
    expect(toCreditMovementRow(movement({ reason: undefined })).reason).toBeNull()
    expect(toCreditMovementRow(movement({ reason: null })).reason).toBeNull()
  })
})

describe('memberName', () => {
  it('assemble prénom et nom', () => {
    expect(memberName({ givenName: 'Sara', familyName: 'Bennani' }, 'Compte fermé')).toBe(
      'Sara Bennani',
    )
  })

  /** Une fiche survit à la fermeture d'un compte : l'écran doit écrire quelque chose. */
  it('replie sur le libellé fourni quand il ne reste aucun nom', () => {
    expect(memberName({ givenName: '', familyName: '  ' }, 'Compte fermé')).toBe('Compte fermé')
  })
})

describe('subscriptionOf', () => {
  /** L'API OMET le champ plutôt que de le mettre à nul : un gabarit teste mal une clé absente. */
  it('normalise un abonnement absent en null', () => {
    expect(subscriptionOf(organization())).toBeNull()
    expect(subscriptionOf(organization({ subscription: undefined }))).toBeNull()
  })

  it('rend l abonnement quand il existe', () => {
    const subscription = { plan: 'starter', status: 'ACTIVE' }

    expect(subscriptionOf(organization({ subscription }))).toStrictEqual(subscription)
  })
})

describe('organizationScreenState', () => {
  it('montre la fiche quand elle est chargée', () => {
    expect(
      organizationScreenState({ pending: false, notFound: false, error: false, loaded: true }),
    ).toBe('sheet')
  })

  it('dit le chargement en cours', () => {
    expect(
      organizationScreenState({ pending: true, notFound: false, error: false, loaded: false }),
    ).toBe('loading')
  })

  /**
   * INTROUVABLE N'EST PAS UNE PANNE. Proposer de réessayer sur une entreprise qui n'existe pas
   * ferait tourner en rond, et l'écran doit pouvoir le dire autrement — sans alerte rouge.
   */
  it('distingue l introuvable de la panne', () => {
    expect(
      organizationScreenState({ pending: false, notFound: true, error: false, loaded: false }),
    ).toBe('notFound')
    expect(
      organizationScreenState({ pending: false, notFound: false, error: true, loaded: false }),
    ).toBe('error')
  })

  /** L'introuvable passe devant la panne : un 404 avalé laisse le statut global à « en erreur ». */
  it('fait passer l introuvable devant la panne', () => {
    expect(
      organizationScreenState({ pending: false, notFound: true, error: true, loaded: false }),
    ).toBe('notFound')
  })

  /** Chargement terminé mais rien en main : c'est une panne, pas une fiche vide à afficher. */
  it('traite une fiche absente sans erreur déclarée comme une panne', () => {
    expect(
      organizationScreenState({ pending: false, notFound: false, error: false, loaded: false }),
    ).toBe('error')
  })
})
