import { describe, expect, it } from 'vitest'

import { TYPING_TOTAL_MS, typingDelay, typingSteps } from './typewriter'

/**
 * Ce qu'on vérifie ici n'est pas « ça anime », c'est **ce que la suite d'étapes garantit** : qu'on
 * n'efface jamais ce qui n'a pas changé, et qu'on arrive exactement à destination. Le reste — jouer
 * les étapes, les espacer, les refuser quand le mouvement est refusé — appartient au composant.
 */
describe('typingSteps', () => {
  it('ne rend aucune étape quand le texte ne change pas', () => {
    expect(typingSteps('Développeur Symfony', 'Développeur Symfony')).toEqual([])
    expect(typingSteps('', '')).toEqual([])
  })

  it('tape lettre à lettre depuis un texte vide', () => {
    expect(typingSteps('', 'abc')).toEqual(['a', 'ab', 'abc'])
  })

  it('efface lettre à lettre vers un texte vide', () => {
    expect(typingSteps('abc', '')).toEqual(['ab', 'a', ''])
  })

  it("n'efface QUE ce qui diffère, et garde le préfixe commun", () => {
    // « Dév · 5 ans » → « Dév · 7 ans » : le préfixe « Dév · » ne doit jamais être retouché.
    const steps = typingSteps('Dév · 5 ans', 'Dév · 7 ans')

    expect(steps.every((step) => step.startsWith('Dév · '))).toBe(true)
  })

  it('rallonge sans rien effacer quand le nouveau texte prolonge l’ancien', () => {
    expect(typingSteps('Dév', 'Dév senior')).toEqual([
      'Dév ',
      'Dév s',
      'Dév se',
      'Dév sen',
      'Dév seni',
      'Dév senio',
      'Dév senior',
    ])
  })

  it('arrive exactement au texte visé', () => {
    const steps = typingSteps('Développeur Symfony · 5 ans', 'Chef de projet · 10 ans · Master')

    expect(steps.at(-1)).toBe('Chef de projet · 10 ans · Master')
  })

  it('ne rend jamais deux fois le même état de suite', () => {
    const steps = typingSteps('Consultant SAP', 'Consultant SAP senior · Master')

    expect(steps.every((step, index) => step !== steps[index - 1])).toBe(true)
  })

  it('traite les caractères accentués comme les autres', () => {
    expect(typingSteps('éa', 'éb')).toEqual(['é', 'éb'])
  })
})

describe('typingDelay', () => {
  it('tient le budget de temps sur un écart courant', () => {
    const steps = typingSteps('', 'Développeur Symfony · 5 ans')

    expect(steps.length * typingDelay(steps.length)).toBeLessThanOrEqual(TYPING_TOTAL_MS * 1.1)
  })

  it('ne descend pas sous la cadence lisible sur un écart énorme', () => {
    // 500 étapes à budget constant donneraient ~1 ms : ce serait un clignotement, pas une frappe.
    expect(typingDelay(500)).toBe(10)
  })

  it('ne dépasse pas la cadence haute sur un écart minuscule', () => {
    expect(typingDelay(1)).toBe(30)
  })

  it('rend une valeur jouable même sans étape', () => {
    expect(typingDelay(0)).toBeGreaterThan(0)
  })
})
