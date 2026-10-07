import { describe, expect, it } from 'vitest'

import { toVolumeQuery, volumeKey } from './volume'

import type { SearchCriterion } from '#shared/types/search'

const criterion = (facet: string, value: string): SearchCriterion => ({ facet, value, group: 'x' })

describe('toVolumeQuery', () => {
  it('ne compte rien sans critère', () => {
    expect(toVolumeQuery([])).toEqual({})
  })

  it('relaie chaque facette sous son propre nom', () => {
    expect(
      toVolumeQuery([criterion('job', 'Chef de projet'), criterion('experienceMin', '10')]),
    ).toEqual({ job: 'Chef de projet', experienceMin: '10' })
  })

  // Le point de la task : PHP écrase les clés répétées à l'analyse de la requête, donc `q=php&q=java`
  // compterait « java » seul — un compte trop étroit, et rien ne le signalerait à l'écran.
  it('groupe les mots-clés sous une clé de TABLEAU', () => {
    expect(toVolumeQuery([criterion('q', 'php'), criterion('q', 'java')])).toEqual({
      'q[]': ['php', 'java'],
    })
  })

  it('garde la notation tableau même pour un seul mot-clé', () => {
    expect(toVolumeQuery([criterion('q', 'php')])).toEqual({ 'q[]': ['php'] })
  })

  it('écarte les valeurs vides ou blanches', () => {
    expect(
      toVolumeQuery([criterion('job', '   '), criterion('q', ''), criterion('q', 'php')]),
    ).toEqual({ 'q[]': ['php'] })
  })

  it('rogne les espaces autour des valeurs', () => {
    expect(toVolumeQuery([criterion('job', '  Chef de projet  ')])).toEqual({
      job: 'Chef de projet',
    })
  })
})

describe('volumeKey', () => {
  // C'est elle qui décide qu'une réponse est périmée : deux comptages en vol peuvent revenir dans le
  // désordre, et l'état courant du formulaire fait foi.
  it('sépare deux jeux de critères différents', () => {
    expect(volumeKey([criterion('job', 'Chef de projet')])).not.toBe(
      volumeKey([criterion('job', 'Développeur')]),
    )
  })

  it('confond deux jeux qui comptent la même chose', () => {
    expect(volumeKey([criterion('job', 'Chef de projet'), criterion('q', '  ')])).toBe(
      volumeKey([criterion('job', 'Chef de projet')]),
    )
  })

  // Un curseur ramené à sa valeur d'origine, un niveau d'études coché puis décoché : le formulaire a
  // bougé, les critères non — recompter serait un appel pour rien.
  it('ne bouge pas quand un champ revient à son état de départ', () => {
    const before = volumeKey([criterion('job', 'Chef de projet')])
    const after = volumeKey([criterion('job', 'Chef de projet'), criterion('educationLevel', '')])

    expect(after).toBe(before)
  })
})
