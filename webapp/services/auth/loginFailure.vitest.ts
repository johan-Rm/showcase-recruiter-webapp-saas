import { describe, expect, it } from 'vitest'

import { apiLoginFailure, bffLoginFailure } from './loginFailure'

/**
 * Pourquoi une connexion a été refusée.
 *
 * Ce que ces tests protègent tient en une phrase : **un compte suspendu ne doit jamais être pris
 * pour un mot de passe faux**. La confusion coûte cher — elle envoie réinitialiser un mot de passe
 * correct, et laisse la personne sans explication de ce qui bloque vraiment.
 *
 * Le motif traverse deux sauts et change de forme à chacun, d'où deux lectures distinctes.
 */

/** Ce que le client HTTP construit quand une réponse porte un corps d'erreur. */
const httpError = (body: unknown): unknown => ({ data: body })

describe('apiLoginFailure — la réponse brute de l’API Symfony', () => {
  it('reconnaît la suspension au motif que l’API nomme', () => {
    expect(apiLoginFailure(httpError({ code: 401, message: 'account_suspended' }))).toBe(
      'account_suspended',
    )
  })

  it('rend le motif générique sur un mot de passe faux', () => {
    expect(apiLoginFailure(httpError({ code: 401, message: 'Invalid credentials.' }))).toBe(
      'invalid_credentials',
    )
  })
})

describe('bffLoginFailure — la réponse que le BFF a composée', () => {
  it('reconnaît la suspension relayée par le BFF', () => {
    expect(
      bffLoginFailure(httpError({ statusCode: 401, data: { reason: 'account_suspended' } })),
    ).toBe('account_suspended')
  })

  it('rend le motif générique quand le BFF relaie l’autre cas', () => {
    expect(
      bffLoginFailure(httpError({ statusCode: 401, data: { reason: 'invalid_credentials' } })),
    ).toBe('invalid_credentials')
  })
})

/**
 * Le point qui compte vraiment : **on ne devine pas un motif qu'on n'a pas lu**. Une panne réseau,
 * une passerelle qui répond du HTML, un corps tronqué — rien de tout cela n'est une suspension, et
 * annoncer un compte suspendu à quelqu'un dont le compte va bien serait pire que le message vague.
 */
describe('tout ce qui n’est pas lisible retombe sur le motif générique', () => {
  it.each([
    ['une valeur absente', undefined],
    ['une valeur nulle', null],
    ['une panne réseau, sans corps', new Error('fetch failed')],
    ['un corps qui n’est pas un objet', httpError('<html>502 Bad Gateway</html>')],
    ['un corps sans le champ attendu', httpError({ statusCode: 500 })],
    ['un motif qui n’est pas une chaîne', httpError({ data: { reason: 42 } })],
    ['un motif inconnu', httpError({ data: { reason: 'quota_exceeded' } })],
  ])('%s', (_label, cause) => {
    expect(apiLoginFailure(cause)).toBe('invalid_credentials')
    expect(bffLoginFailure(cause)).toBe('invalid_credentials')
  })
})
