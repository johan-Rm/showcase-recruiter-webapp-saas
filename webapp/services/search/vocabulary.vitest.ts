import { describe, expect, it } from 'vitest'

import { isUnknownJobTerm } from './vocabulary'

import type { JobTermLookup } from './vocabulary'

/** Le cas nominal : une interrogation revenue, sans rien à proposer. */
const lookup = (over: Partial<JobTermLookup> = {}): JobTermLookup => ({
  term: 'plombier',
  suggestions: [],
  loading: false,
  failed: false,
  minChars: 2,
  ...over,
})

describe('isUnknownJobTerm', () => {
  it('déclare inconnu un terme dont la liste revient VIDE', () => {
    expect(isUnknownJobTerm(lookup())).toBe(true)
  })

  // Le cœur de la règle. « développeur » est un libellé VARIANTE : le filtre l'accepte, mais les
  // propositions n'affichent que les libellés canoniques, donc il n'y figure pas. Se fier à
  // « le terme est-il dans la liste ? » le déclarerait inconnu à tort.
  it('se tait dès que la liste porte quelque chose, même sans le terme tapé', () => {
    expect(
      isUnknownJobTerm(
        lookup({ term: 'développeur', suggestions: [{ value: 'Développeur logiciel' }] }),
      ),
    ).toBe(false)
  })

  // Une panne ne prouve rien sur le mot — c'est tout l'intérêt de distinguer l'échec du vide.
  it('se tait quand l’interrogation a ÉCHOUÉ', () => {
    expect(isUnknownJobTerm(lookup({ failed: true }))).toBe(false)
  })

  it('se tait tant que l’interrogation est EN VOL', () => {
    expect(isUnknownJobTerm(lookup({ loading: true }))).toBe(false)
  })

  // Sous le plancher, aucune interrogation n'est même tentée : la liste vide n'y veut rien dire.
  it('se tait sur un terme trop court pour avoir été cherché', () => {
    expect(isUnknownJobTerm(lookup({ term: 'p' }))).toBe(false)
  })

  it('se tait sur un champ vide, et ne compte pas les espaces', () => {
    expect(isUnknownJobTerm(lookup({ term: '' }))).toBe(false)
    expect(isUnknownJobTerm(lookup({ term: '   ' }))).toBe(false)
  })
})
