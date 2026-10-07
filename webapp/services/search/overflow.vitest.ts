import { describe, expect, it } from 'vitest'

import { overflowScreenState, pageCount, toOverflowRow } from './overflow'

import type { AdminOverflowingSearch } from '../../shared/types/admin-search'

const translate = (key: string): string => key

const search = (overrides: Partial<AdminOverflowingSearch> = {}): AdminOverflowingSearch => ({
  id: 's1',
  name: '',
  criteria: [],
  organizationName: 'Atlas RH',
  ownerName: 'Omar Alaoui',
  matchCount: 266,
  servedCount: 100,
  isClosed: false,
  lastActivityAt: '2026-08-12T09:00:00+00:00',
  ...overrides,
})

describe('toOverflowRow', () => {
  it('garde le nom saisi quand il y en a un', () => {
    const row = toOverflowRow(search({ name: 'Devs Casablanca' }), translate)

    expect(row.title).toBe('Devs Casablanca')
    expect(row.isAutoNamed).toBe(false)
  })

  /**
   * Une recherche non renommée n'a PAS de nom : ses critères en tiennent lieu, comme sur la carte
   * de recherche côté entreprise. Un titre vide laisserait une ligne anonyme dans un inventaire
   * dont tout l'objet est de désigner une recherche précise.
   */
  it("compose le titre à partir des critères quand la recherche n'a pas de nom", () => {
    const row = toOverflowRow(
      search({ criteria: [{ facet: 'job', value: 'Développeur logiciel', group: 'metier' }] }),
      translate,
    )

    expect(row.title).toContain('Développeur logiciel')
    expect(row.isAutoNamed).toBe(true)
  })

  it('signale un titre impossible plutôt que de le fabriquer', () => {
    const row = toOverflowRow(search({ name: '   ', criteria: [] }), translate)

    expect(row.title).toBe('')
    expect(row.isAutoNamed).toBe(true)
  })

  /** L'ÉCART est la raison d'être de l'écran : ce que le vivier ramène, moins ce qu'on en montre. */
  it('calcule ce qui ne sera jamais montré', () => {
    expect(
      toOverflowRow(search({ matchCount: 266, servedCount: 100 }), translate).hiddenCount,
    ).toBe(166)
  })

  /** Le nombre servi ne dépasse jamais le nombre réel ; si c'était le cas, l'écart resterait nul. */
  it('ne rend jamais un écart négatif', () => {
    expect(toOverflowRow(search({ matchCount: 80, servedCount: 100 }), translate).hiddenCount).toBe(
      0,
    )
  })

  it('reporte le total réel, l’état et la dernière activité tels quels', () => {
    const row = toOverflowRow(search({ isClosed: true }), translate)

    expect(row.matchCount).toBe(266)
    expect(row.isClosed).toBe(true)
    expect(row.lastActivityAt).toBe('2026-08-12T09:00:00+00:00')
    expect(row.organizationName).toBe('Atlas RH')
    expect(row.ownerName).toBe('Omar Alaoui')
  })
})

describe('overflowScreenState', () => {
  it('montre la liste dès qu’il y a quelque chose à montrer', () => {
    expect(overflowScreenState({ pending: false, error: false, total: 5 })).toBe('list')
  })

  it('annonce le vide quand rien ne dépasse la borne', () => {
    expect(overflowScreenState({ pending: false, error: false, total: 0 })).toBe('empty')
  })

  it('annonce la panne', () => {
    expect(overflowScreenState({ pending: false, error: true, total: 0 })).toBe('error')
  })

  /**
   * L'ORDRE EST LA RÈGLE, et c'est pour lui que cette fonction existe. Un inventaire qui n'a pas pu
   * être chargé n'est pas un inventaire sans débordement : les confondre annoncerait une bonne
   * nouvelle que personne n'a constatée.
   */
  it('ne confond pas une panne avec une absence de débordement', () => {
    expect(overflowScreenState({ pending: false, error: true, total: 0 })).not.toBe('empty')
  })

  /** Une nouvelle tentative en cours ne doit pas continuer d'afficher l'échec de la précédente. */
  it('le chargement passe devant tout le reste', () => {
    expect(overflowScreenState({ pending: true, error: true, total: 0 })).toBe('loading')
    expect(overflowScreenState({ pending: true, error: false, total: 5 })).toBe('loading')
  })
})

describe('pageCount', () => {
  it('compte les pages', () => {
    expect(pageCount(5, 25)).toBe(1)
    expect(pageCount(25, 25)).toBe(1)
    expect(pageCount(26, 25)).toBe(2)
  })

  /** « Page 1 sur 0 » se lit comme une panne : il y a toujours au moins une page. */
  it('rend au moins une page, même sans résultat', () => {
    expect(pageCount(0, 25)).toBe(1)
  })

  it('ne divise pas par zéro', () => {
    expect(pageCount(10, 0)).toBe(1)
  })
})
