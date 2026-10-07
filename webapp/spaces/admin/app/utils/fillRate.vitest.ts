import { describe, expect, it } from 'vitest'

import { fillRatePercent } from './fillRate'

// Les chiffres viennent du vivier réel : 2 561 profils, dont 2 561 avec un prénom, 2 213 avec une
// photo, 22 avec un WhatsApp. Ils gardent le test lisible et ancrent les bornes sur du vrai.
const TOTAL = 2561

describe('fillRatePercent', () => {
  it('rend 100 quand le champ est détenu partout', () => {
    expect(fillRatePercent(TOTAL, TOTAL)).toBe(100)
  })

  it('rend 0 quand le champ n’est détenu nulle part', () => {
    expect(fillRatePercent(0, TOTAL)).toBe(0)
  })

  it('arrondit à l’entier le plus proche', () => {
    // 2213/2561 = 86,41 % → 86 ; 22/2561 = 0,86 % → 1. Le second compte : arrondir vers le bas
    // afficherait « 0 % » sur une mesure qui existe, et la jauge serait vide alors qu'elle ne
    // l'est pas.
    expect(fillRatePercent(2213, TOTAL)).toBe(86)
    expect(fillRatePercent(22, TOTAL)).toBe(1)
  })

  it('rend 0 sur une base vide, jamais NaN', () => {
    // Une base vide est un état normal au démarrage. Un NaN se propagerait dans la largeur de la
    // jauge et la casserait sans rien signaler.
    expect(fillRatePercent(0, 0)).toBe(0)
    expect(Number.isNaN(fillRatePercent(0, 0))).toBe(false)
  })
})
