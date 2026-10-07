import { describe, expect, it } from 'vitest'

import { safeRedirectTarget } from './redirect'

describe('safeRedirectTarget', () => {
  it('accepte un chemin interne, query et hash compris', () => {
    expect(safeRedirectTarget('/candidats/123')).toBe('/candidats/123')
    expect(safeRedirectTarget('/entreprises?statut=en-revue#top')).toBe(
      '/entreprises?statut=en-revue#top',
    )
    expect(safeRedirectTarget('/en/candidats/123')).toBe('/en/candidats/123')
  })

  it('rejette les URL protocol-relative', () => {
    expect(safeRedirectTarget('//evil.tld')).toBeNull()
    expect(safeRedirectTarget('/\\evil.tld')).toBeNull()
  })

  it('rejette schémas et hôtes absolus', () => {
    expect(safeRedirectTarget('https://evil.tld')).toBeNull()
    expect(safeRedirectTarget('javascript:alert(1)')).toBeNull()
    expect(safeRedirectTarget('evil.tld/path')).toBeNull()
  })

  it('rejette une valeur absente', () => {
    expect(safeRedirectTarget(undefined)).toBeNull()
    expect(safeRedirectTarget(null)).toBeNull()
    expect(safeRedirectTarget('')).toBeNull()
  })

  it('rejette une valeur multiple (tableau Vue Router)', () => {
    expect(safeRedirectTarget(['/a', '/b'])).toBeNull()
  })
})
