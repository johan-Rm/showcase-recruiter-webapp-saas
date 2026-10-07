import { describe, expect, it } from 'vitest'

import { countryLabel, languageLabel } from './labels'

describe('countryLabel', () => {
  it('nomme le pays dans la langue demandée', () => {
    expect(countryLabel('MA', 'fr')).toBe('Maroc')
    expect(countryLabel('MA', 'en')).toBe('Morocco')
    expect(countryLabel('BE', 'fr')).toBe('Belgique')
  })

  it("rend le code tel quel quand l'exécution ne sait pas le nommer", () => {
    // `zz` n'est pas une région connue : rien à nommer, le code ressort intact et SANS capitale —
    // « Zz » ne serait ni le code, ni un nom.
    expect(countryLabel('zz', 'fr')).toBe('zz')
  })

  it('rend le code tel quel plutôt que de jeter sur un code mal formé', () => {
    // `Intl.DisplayNames.of()` JETTE sur une entrée qui n'a pas la forme attendue. Sans la garde, la
    // rangée entière cesserait de rendre — c'est la panne qu'a déjà produite une carte incomplète.
    expect(countryLabel('pays-inconnu', 'fr')).toBe('pays-inconnu')
    expect(countryLabel('', 'fr')).toBe('')
  })
})

describe('languageLabel', () => {
  it('nomme la langue dans la langue demandée', () => {
    expect(languageLabel('nl', 'fr')).toBe('Néerlandais')
    expect(languageLabel('nl', 'en')).toBe('Dutch')
    expect(languageLabel('ar', 'fr')).toBe('Arabe')
  })

  it("porte une capitale initiale, que la langue ne mettrait pas d'elle-même", () => {
    // Le panneau n'affiche que des options capitalisées ; un « français » bas serait le seul.
    expect(languageLabel('fr', 'fr').startsWith('F')).toBe(true)
    expect(languageLabel('en', 'fr').startsWith('A')).toBe(true)
  })

  it('rend le code tel quel quand il ne se nomme pas', () => {
    expect(languageLabel('zz', 'fr')).toBe('zz')
  })

  it("traverse sans dommage une écriture qui n'a pas de casse", () => {
    const arabe = languageLabel('ar', 'ar')

    expect(arabe).not.toBe('')
    expect(arabe).toBe(languageLabel('ar', 'ar'))
  })
})
