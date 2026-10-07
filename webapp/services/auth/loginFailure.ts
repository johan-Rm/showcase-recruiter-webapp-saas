import { readErrorString } from './errorReason'

import type { LoginFailureReason } from '#shared/types/auth'

/**
 * Pourquoi une connexion a échoué.
 *
 * Un compte suspendu et un mot de passe faux répondent tous deux par un refus : sans distinction,
 * l'écran conclurait à une erreur de saisie et enverrait réinitialiser un mot de passe correct.
 *
 * Le motif traverse deux sauts, et sa forme change à chacun — d'où deux lectures :
 * l'API le met dans `message`, le BFF le range dans `data.reason`. Tout ce qui n'est pas
 * explicitement une suspension retombe sur le motif générique, y compris une panne réseau : on ne
 * devine pas un motif qu'on n'a pas lu.
 */
const SUSPENDED: LoginFailureReason = 'account_suspended'

/** Lecture côté BFF, sur la réponse brute de l'API Symfony. */
export const apiLoginFailure = (cause: unknown): LoginFailureReason =>
  SUSPENDED === readErrorString(cause, ['data', 'message']) ? SUSPENDED : 'invalid_credentials'

/** Lecture côté navigateur, sur la réponse que le BFF vient de composer. */
export const bffLoginFailure = (cause: unknown): LoginFailureReason =>
  SUSPENDED === readErrorString(cause, ['data', 'data', 'reason'])
    ? SUSPENDED
    : 'invalid_credentials'
