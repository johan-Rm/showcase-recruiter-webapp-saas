import { describe, expect, it } from 'vitest'

import { isArtefactRoute, isArtefactRouteServed } from './artefacts'

describe('isArtefactRoute', () => {
  it('reconnaît le dossier et tout ce qu il contient', () => {
    expect(isArtefactRoute('/artefacts')).toBe(true)
    expect(isArtefactRoute('/artefacts/ux-kit')).toBe(true)
    expect(isArtefactRoute('/artefacts/entreprise/le-vivier')).toBe(true)
  })

  it('ne capture pas une route qui commence par les mêmes lettres', () => {
    expect(isArtefactRoute('/artefacts-de-la-marque')).toBe(false)
    expect(isArtefactRoute('/artefactsx')).toBe(false)
  })

  it('laisse le reste du site tranquille', () => {
    expect(isArtefactRoute('/')).toBe(false)
    expect(isArtefactRoute('/login')).toBe(false)
  })
})

describe('isArtefactRouteServed', () => {
  it('ferme les artefacts quand le drapeau est absent', () => {
    expect(isArtefactRouteServed('/artefacts', false)).toBe(false)
    expect(isArtefactRouteServed('/artefacts/ux-kit', false)).toBe(false)
    expect(isArtefactRouteServed('/artefacts/admin/dashboard', false)).toBe(false)
  })

  it('les ouvre quand il est présent', () => {
    expect(isArtefactRouteServed('/artefacts', true)).toBe(true)
    expect(isArtefactRouteServed('/artefacts/ux-kit', true)).toBe(true)
  })

  it('ne conditionne jamais le reste du site au drapeau', () => {
    expect(isArtefactRouteServed('/', false)).toBe(true)
    expect(isArtefactRouteServed('/login', false)).toBe(true)
    expect(isArtefactRouteServed('/artefacts-de-la-marque', false)).toBe(true)
  })
})
