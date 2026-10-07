import { describe, expect, it } from 'vitest'

import { exportFileName, exportScope } from './export'

import type { CandidateCard } from '../../shared/types/candidate-card'

const candidate = (id: string, revealedEmail: boolean): CandidateCard =>
  ({ id, revealedEmail, revealedPhone: false }) as CandidateCard

describe('exportScope', () => {
  // Le writer ne masque rien : ce qu'il écrit est dévoilé par construction. Un masqué n'a rien à
  // donner, et l'écran doit le dire avant l'appel plutôt que de livrer un fichier trop court.
  it('ne retient que les profils dévoilés, et compte les autres', () => {
    const scope = exportScope([candidate('a', true), candidate('b', false), candidate('c', true)])

    expect(scope.exportable).toEqual(['a', 'c'])
    expect(scope.ignored).toBe(1)
  })

  it('rend une portée vide quand rien n’est dévoilé', () => {
    const scope = exportScope([candidate('a', false)])

    expect(scope.exportable).toEqual([])
    expect(scope.ignored).toBe(1)
  })
})

describe('exportFileName', () => {
  it('reprend la règle du serveur — minuscules, séparateurs en tirets, accents conservés', () => {
    expect(exportFileName('Finance France vers Casablanca')).toBe(
      'profils-finance-france-vers-casablanca.csv',
    )
    expect(exportFileName('Développeur / PHP')).toBe('profils-développeur-php.csv')
  })

  it('nomme une recherche sans nom plutôt que de rendre un fichier anonyme', () => {
    expect(exportFileName('   ')).toBe('profils-sans-nom.csv')
  })
})
