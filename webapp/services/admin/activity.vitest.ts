import { describe, expect, it } from 'vitest'

import { activityScreenState, toActivityRow } from './activity'

import type { AdminActivityEntry } from '../../shared/types/admin-activity'

const UNKNOWN = 'Compte fermé'

const entry = (overrides: Partial<AdminActivityEntry> = {}): AdminActivityEntry => ({
  id: 'v1',
  viewedAt: '2026-08-19T07:06:00+00:00',
  recruiterName: 'Leila Mansouri',
  recruiterEmail: 'atlas.member@demo.showcase-recruiter-webapp-saas.example',
  organizationName: 'Atlas Talent Conseil',
  candidateId: 'c1',
  candidateName: 'Reda Elhasnaoui',
  revealed: true,
  ...overrides,
})

describe('toActivityRow', () => {
  it('reporte la visite telle quelle quand tout est renseigné', () => {
    const row = toActivityRow(entry(), UNKNOWN)

    expect(row).toStrictEqual({
      id: 'v1',
      viewedAt: '2026-08-19T07:06:00+00:00',
      recruiterName: 'Leila Mansouri',
      recruiterEmail: 'atlas.member@demo.showcase-recruiter-webapp-saas.example',
      organizationName: 'Atlas Talent Conseil',
      candidateId: 'c1',
      candidateName: 'Reda Elhasnaoui',
      revealed: true,
    })
  })

  /** L'API omet les champs nuls : l'écran doit tester une valeur, pas la présence d'une clé. */
  it('rend une adresse absente comme nulle', () => {
    expect(toActivityRow(entry({ recruiterEmail: undefined }), UNKNOWN).recruiterEmail).toBeNull()
    expect(toActivityRow(entry({ recruiterEmail: null }), UNKNOWN).recruiterEmail).toBeNull()
  })

  /**
   * Une trace survit à la fermeture d'un compte, et rien ne garantit qu'une personne effacée ait
   * laissé un nom lisible. L'écran doit écrire quelque chose plutôt qu'une case blanche.
   */
  it('remplace un nom vide par le libellé de repli', () => {
    const row = toActivityRow(
      entry({ recruiterName: '   ', organizationName: '', candidateName: ' ' }),
      UNKNOWN,
    )

    expect(row.recruiterName).toBe(UNKNOWN)
    expect(row.organizationName).toBe(UNKNOWN)
    expect(row.candidateName).toBe(UNKNOWN)
  })

  it('conserve la mention de dévoilement, vraie comme fausse', () => {
    expect(toActivityRow(entry({ revealed: false }), UNKNOWN).revealed).toBe(false)
    expect(toActivityRow(entry({ revealed: true }), UNKNOWN).revealed).toBe(true)
  })
})

describe('activityScreenState', () => {
  it('montre le fil dès qu il y a des visites', () => {
    expect(activityScreenState({ pending: false, error: false, total: 3 })).toBe('list')
  })

  it('dit le vide quand il n y a rien à montrer', () => {
    expect(activityScreenState({ pending: false, error: false, total: 0 })).toBe('empty')
  })

  it('dit l échec quand le chargement a raté', () => {
    expect(activityScreenState({ pending: false, error: true, total: 0 })).toBe('error')
  })

  /**
   * L'ORDRE est la règle : un rechargement en cours passe devant l'échec du précédent, sinon
   * l'écran continuerait d'afficher une panne pendant qu'il la répare.
   */
  it('fait passer le chargement devant l échec', () => {
    expect(activityScreenState({ pending: true, error: true, total: 0 })).toBe('loading')
  })

  /**
   * Et l'échec passe devant le vide : un fil qui n'a pas pu être chargé n'est pas un fil sans
   * visite. Les confondre annoncerait que personne ne consulte les profils, alors qu'on n'en sait
   * rien — c'est le défaut qui a été corrigé à l'écran, où le compteur affichait « 0 » sous le
   * message d'erreur.
   */
  it('fait passer l échec devant le vide', () => {
    expect(activityScreenState({ pending: false, error: true, total: 0 })).not.toBe('empty')
  })
})
