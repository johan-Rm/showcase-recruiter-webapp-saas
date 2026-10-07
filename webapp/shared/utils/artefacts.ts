// Maquettes de conception (`/artefacts/*`, espace public) : ouvertes en dev et en preprod, fermées
// en production. La décision est isolée ici, pure (aucun import framework), pour être testable sans
// runtime Nuxt — le middleware qui l'appelle ne fait plus que lever le 404.

export const ARTEFACTS_PREFIX = '/artefacts'

/**
 * Une route relève-t-elle des artefacts ? Vrai pour le dossier lui-même et tout ce qu'il contient.
 *
 * Le test porte sur le segment complet, pas sur le préfixe brut : `startsWith('/artefacts')` seul
 * capturerait `/artefacts-de-la-marque`, une page publique qui n'a rien à voir et se retrouverait
 * fermée en production sans que rien ne l'explique.
 */
export const isArtefactRoute = (path: string): boolean =>
  path === ARTEFACTS_PREFIX || path.startsWith(`${ARTEFACTS_PREFIX}/`)

/**
 * La route doit-elle être servie ? Seules les routes d'artefacts dépendent du drapeau ; tout le
 * reste du site passe sans condition.
 *
 * `enabled` vient de `runtimeConfig.public.artefacts`, et non de `import.meta.dev` : la preprod
 * builde en production, où ce dernier vaut `false` — les maquettes y seraient fermées, alors que
 * c'est précisément l'environnement où on veut les montrer.
 */
export const isArtefactRouteServed = (path: string, enabled: boolean): boolean =>
  !isArtefactRoute(path) || enabled
