import { describe, expect, it } from 'vitest'

import { BOOT_TRAIL_FILL_MS, bootTrailAdvances, bootTrailDurationMs } from './bootTrail'

describe('bootTrailAdvances', () => {
  it('donne un instant par TRAIT, pas un par jalon', () => {
    // Le premier jalon est allumé d'entrée : trois jalons, deux traits, deux avancées.
    expect(bootTrailAdvances(3)).toEqual([0, BOOT_TRAIL_FILL_MS])
  })

  it('fait partir la première avancée immédiatement', () => {
    // Sans ce zéro, le rideau resterait sur un parcours vide le temps d'une attente inutile.
    expect(bootTrailAdvances(3).at(0)).toBe(0)
  })

  it('espace les avancées de la durée de remplissage d’un trait', () => {
    const advances = bootTrailAdvances(4)

    expect(advances).toHaveLength(3)
    expect(advances[1]! - advances[0]!).toBe(BOOT_TRAIL_FILL_MS)
    expect(advances[2]! - advances[1]!).toBe(BOOT_TRAIL_FILL_MS)
  })

  it('ne rend rien quand il n’y a aucun trait à remplir', () => {
    expect(bootTrailAdvances(1)).toEqual([])
    expect(bootTrailAdvances(0)).toEqual([])
  })
})

describe('bootTrailDurationMs', () => {
  it('vaut 1,2 s pour les trois jalons du parcours', () => {
    // C'est le PLANCHER d'affichage du rideau, et le seul chiffre que l'utilisateur subit à chaque
    // démarrage : il a été arbitré, il ne doit pas dériver sans qu'on le décide.
    expect(bootTrailDurationMs(3)).toBe(1200)
  })

  it('couvre le dernier trait jusqu’au bout', () => {
    const steps = 3
    const lastAdvance = bootTrailAdvances(steps).at(-1)!

    expect(bootTrailDurationMs(steps)).toBe(lastAdvance + BOOT_TRAIL_FILL_MS)
  })

  it('n’impose aucune attente sans trait à remplir', () => {
    expect(bootTrailDurationMs(1)).toBe(0)
    expect(bootTrailDurationMs(0)).toBe(0)
  })
})
