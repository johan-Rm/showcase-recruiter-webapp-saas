import { describe, expect, it } from 'vitest'

import {
  AVAILABLE_LOCALES,
  FALLBACK_LOCALE,
  SUPPORTED_LOCALES,
  isLocaleCode,
  normalizeLocale,
} from './locale'

describe('isLocaleCode', () => {
  it('accepte les codes du registre', () => {
    expect(isLocaleCode('fr')).toBe(true)
    expect(isLocaleCode('en')).toBe(true)
    expect(isLocaleCode('ar')).toBe(true)
  })

  it('rejette tout le reste', () => {
    expect(isLocaleCode('de')).toBe(false)
    expect(isLocaleCode('fr-FR')).toBe(false)
    expect(isLocaleCode('FR')).toBe(false)
    expect(isLocaleCode('')).toBe(false)
    expect(isLocaleCode(null)).toBe(false)
    expect(isLocaleCode(undefined)).toBe(false)
    expect(isLocaleCode(42)).toBe(false)
  })
})

describe('normalizeLocale', () => {
  it('réduit une balise régionale à son code', () => {
    expect(normalizeLocale('fr-FR')).toBe('fr')
    expect(normalizeLocale('en-US')).toBe('en')
    expect(normalizeLocale('ar-MA')).toBe('ar')
  })

  it('est insensible à la casse', () => {
    expect(normalizeLocale('AR')).toBe('ar')
    expect(normalizeLocale('EN-GB')).toBe('en')
  })

  it('retombe sur le défaut pour une valeur inconnue, vide ou absente', () => {
    expect(normalizeLocale('de')).toBe(FALLBACK_LOCALE)
    expect(normalizeLocale('')).toBe(FALLBACK_LOCALE)
    expect(normalizeLocale(null)).toBe(FALLBACK_LOCALE)
    expect(normalizeLocale(undefined)).toBe(FALLBACK_LOCALE)
  })
})

describe('registre', () => {
  it('expose les 3 langues dans un ordre stable', () => {
    expect(SUPPORTED_LOCALES.map((locale) => locale.code)).toEqual(['fr', 'en', 'ar'])
  })

  it('marque l’arabe en RTL, les autres en LTR', () => {
    expect(AVAILABLE_LOCALES.ar.dir).toBe('rtl')
    expect(AVAILABLE_LOCALES.fr.dir).toBe('ltr')
    expect(AVAILABLE_LOCALES.en.dir).toBe('ltr')
  })

  it('a le défaut dans le registre', () => {
    expect(isLocaleCode(FALLBACK_LOCALE)).toBe(true)
  })
})
