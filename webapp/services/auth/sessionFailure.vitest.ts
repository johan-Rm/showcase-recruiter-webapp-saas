import { describe, expect, it } from 'vitest'

import { isSessionExpired } from './sessionFailure'

/**
 * Quand une session est-elle réputée close ?
 *
 * Ce que ces tests protègent tient en deux phrases, et elles tirent en sens contraire. **Une
 * session close doit être reconnue** — sinon la personne reste devant un écran qui porte son nom
 * et n'affiche rien, sans savoir qu'il faut se reconnecter. Et **rien d'autre ne doit l'être** :
 * conclure à tort déconnecte quelqu'un en plein travail, ce qui est bien pire que le message vague.
 */

/** Ce que le client HTTP construit quand une réponse porte un corps d'erreur. */
const httpError = (body: unknown): unknown => ({ data: body })

describe('la session close est reconnue au motif que le BFF nomme', () => {
  it('sur le refus que le relais compose', () => {
    expect(
      isSessionExpired(
        httpError({
          statusCode: 401,
          statusMessage: 'Session expirée',
          data: { reason: 'session_expired' },
        }),
      ),
    ).toBe(true)
  })
})

/**
 * Le code 401 seul ne conclut rien : il couvre aussi bien un appel mal formé qu'un endpoint qui
 * refuse pour une autre raison. Un refus de droits (403) encore moins — l'accès a disparu, pas la
 * session, et fermer celle-ci renverrait au login quelqu'un dont la connexion est parfaitement
 * valide.
 */
describe('tout le reste laisse la session ouverte', () => {
  it.each([
    ['une valeur absente', undefined],
    ['une valeur nulle', null],
    ['une panne réseau, sans corps', new Error('fetch failed')],
    ['un corps qui n’est pas un objet', httpError('<html>502 Bad Gateway</html>')],
    ['un 401 nu, sans motif', httpError({ statusCode: 401, statusMessage: 'Unauthorized' })],
    ['un refus de droits', httpError({ statusCode: 403, data: { reason: 'forbidden' } })],
    [
      'le motif d’un login refusé',
      httpError({ statusCode: 401, data: { reason: 'account_suspended' } }),
    ],
    ['un motif qui n’est pas une chaîne', httpError({ data: { reason: 42 } })],
    ['un motif inconnu', httpError({ data: { reason: 'quota_exceeded' } })],
  ])('%s', (_label, cause) => {
    expect(isSessionExpired(cause)).toBe(false)
  })
})
