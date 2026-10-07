import { describe, expect, it } from 'vitest'

import { resolveNavRoute, stripLocaleSuffix } from './navRoute'

// Les routes réellement déclarées par la navigation admin, dans l'ordre du composable.
const NAV_ROUTES = [
  'index',
  'entreprises',
  'candidats',
  'utilisateurs',
  'jarvi',
  'referentiels',
  'vivier',
  'compte',
]

describe('stripLocaleSuffix', () => {
  it('retire le suffixe des trois langues', () => {
    expect(stripLocaleSuffix('candidats___fr')).toBe('candidats')
    expect(stripLocaleSuffix('candidats___en')).toBe('candidats')
    expect(stripLocaleSuffix('candidats___ar')).toBe('candidats')
  })

  it('laisse intact un nom sans suffixe', () => {
    expect(stripLocaleSuffix('candidats')).toBe('candidats')
    expect(stripLocaleSuffix('candidats-id')).toBe('candidats-id')
  })
})

describe('resolveNavRoute', () => {
  it('reconnaît chaque route de la navigation, suffixée ou non', () => {
    for (const route of NAV_ROUTES) {
      expect(resolveNavRoute(route, NAV_ROUTES)).toBe(route)
      expect(resolveNavRoute(`${route}___ar`, NAV_ROUTES)).toBe(route)
    }
  })

  it('fait hériter une fiche du libellé de sa section', () => {
    expect(resolveNavRoute('candidats-id', NAV_ROUTES)).toBe('candidats')
    expect(resolveNavRoute('candidats-id___fr', NAV_ROUTES)).toBe('candidats')
  })

  it('rend null sur une route hors navigation — ce n’est pas une erreur', () => {
    expect(resolveNavRoute('template-dashboard', NAV_ROUTES)).toBeNull()
    expect(resolveNavRoute('login___en', NAV_ROUTES)).toBeNull()
    expect(resolveNavRoute('', NAV_ROUTES)).toBeNull()
  })

  it('rend null sur ce qui n’est pas un nom de route', () => {
    expect(resolveNavRoute(null, NAV_ROUTES)).toBeNull()
    expect(resolveNavRoute(undefined, NAV_ROUTES)).toBeNull()
    expect(resolveNavRoute(Symbol('route'), NAV_ROUTES)).toBeNull()
  })

  it('ne remonte jamais au-delà de ce qui est déclaré', () => {
    // `candidats` est déclarée, `entreprises-membres` ne l'est pas : la remontée s'arrête à la
    // racine du nom sans jamais inventer un parent absent de la liste.
    expect(resolveNavRoute('entreprises-membres-id', NAV_ROUTES)).toBe('entreprises')
    expect(resolveNavRoute('vivier-x', ['candidats'])).toBeNull()
  })
})
