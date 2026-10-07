/**
 * Pourquoi une connexion a été refusée.
 *
 * `account_suspended` est le motif que l'API renvoie quand l'entreprise du recruteur a été refusée.
 * Il voyage tel quel de l'API au BFF puis à l'écran, où il choisit le message affiché.
 */
export type LoginFailureReason = 'account_suspended' | 'invalid_credentials'
