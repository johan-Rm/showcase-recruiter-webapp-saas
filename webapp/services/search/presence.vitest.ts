import { describe, expect, it } from 'vitest'

import { hasStayWithin, isPresentInMorocco, stayCities } from './presence'

const july = (day: number) => new Date(`2026-07-${String(day).padStart(2, '0')}T12:00:00Z`)

describe('isPresentInMorocco', () => {
  const window = [{ startDate: '2026-07-08', endDate: '2026-07-24', city: 'Casablanca' }]

  it('est vrai pendant la fenêtre', () => {
    expect(isPresentInMorocco(window, july(15))).toBe(true)
  })

  it('inclut les deux bornes', () => {
    expect(isPresentInMorocco(window, july(8))).toBe(true)
    expect(isPresentInMorocco(window, july(24))).toBe(true)
  })

  it('est faux avant et après', () => {
    expect(isPresentInMorocco(window, july(7))).toBe(false)
    expect(isPresentInMorocco(window, july(25))).toBe(false)
  })

  it('est faux sans aucune fenêtre déclarée', () => {
    expect(isPresentInMorocco([], july(15))).toBe(false)
  })

  it('accepte des bornes horodatées', () => {
    expect(
      isPresentInMorocco(
        [
          {
            startDate: '2026-07-08T00:00:00+00:00',
            endDate: '2026-07-24T00:00:00+00:00',
            city: null,
          },
        ],
        july(24),
      ),
    ).toBe(true)
  })
})

describe('hasStayWithin', () => {
  const casa = { startDate: '2026-07-20', endDate: '2026-07-30', city: 'Casablanca' }
  const rabat = { startDate: '2026-12-01', endDate: '2026-12-10', city: 'Rabat' }

  it('retient un séjour qui chevauche la fenêtre', () => {
    expect(hasStayWithin([casa], 30, null, july(1))).toBe(true)
  })

  it('écarte un séjour au-delà de la fenêtre', () => {
    expect(hasStayWithin([casa], 15, null, july(1))).toBe(false)
  })

  it('retient un séjour en cours, commencé avant la fenêtre', () => {
    expect(hasStayWithin([casa], 0, null, july(25))).toBe(true)
  })

  it('écarte un séjour déjà terminé', () => {
    expect(hasStayWithin([casa], 60, null, new Date('2026-08-01T12:00:00Z'))).toBe(false)
  })

  // La règle du serveur, et la seule qui distingue ce filtre de deux filtres séparés : le séjour
  // dans la fenêtre doit être CELUI de la ville demandée.
  it('exige que la fenêtre et la ville portent sur le même séjour', () => {
    expect(hasStayWithin([casa, rabat], 30, 'Casablanca', july(1))).toBe(true)
    expect(hasStayWithin([casa, rabat], 30, 'Rabat', july(1))).toBe(false)
  })

  it('compare les villes sans tenir compte de la casse', () => {
    expect(hasStayWithin([casa], 30, 'casablanca', july(1))).toBe(true)
  })

  it('est faux sans aucun séjour déclaré', () => {
    expect(hasStayWithin([], 60, null, july(1))).toBe(false)
  })
})

describe('stayCities', () => {
  it('dédoublonne et range les villes du jeu', () => {
    expect(
      stayCities([
        [{ startDate: '2026-07-01', endDate: '2026-07-05', city: 'Rabat' }],
        [{ startDate: '2026-07-01', endDate: '2026-07-05', city: 'Casablanca' }],
        [{ startDate: '2026-08-01', endDate: '2026-08-05', city: 'Rabat' }],
      ]),
    ).toEqual(['Casablanca', 'Rabat'])
  })

  it('ignore les séjours sans ville', () => {
    expect(
      stayCities([
        [{ startDate: '2026-07-01', endDate: '2026-07-05', city: null }],
        [{ startDate: '2026-07-01', endDate: '2026-07-05', city: '  ' }],
      ]),
    ).toEqual([])
  })
})
