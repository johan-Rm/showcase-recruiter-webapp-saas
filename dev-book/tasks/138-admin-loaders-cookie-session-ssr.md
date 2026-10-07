# TASK 138 — admin : forwarder le cookie de session au SSR dans les loaders de bootstrap

- **Créée le** : 2026-07-26.
- **Statut** : ✅ fait le 2026-07-26 — prouvé au run (le dashboard charge, appels `/api/admin/*`
  en 200). Type-check des 4 espaces vert.
- **Type** : Front — Webapp Nuxt (`spaces/admin`), correctif.
- **Placement** : corrige un bug introduit par `055b` (et propagé à chaque migration de domaine) —
  révélé au run sur la home `/` (« error.unexpected »).
- **Dépend de** : `055b` (orchestrateur), `138` corrige `130`/`133`/`134`/`136`/`137`.

## Contexte

Les loaders appelaient le BFF avec `$fetch` brut. Au **SSR**, `$fetch` ne transmet pas les en-têtes
de la requête entrante — dont le **cookie de session**. Le BFF (`apiFetchForUser` →
`requireUserSession(event)`) ne trouvait donc pas de session → 401 → `initCoreData` passait
`status='error'`. Côté client le bug était invisible (le navigateur envoie le cookie lui-même), d'où
un échec **SSR-only** qui cassait le premier rendu.

## Livré

- `spaces/admin/app/bootstrap/loaders.ts` — les 5 loaders utilisent **`useRequestFetch()`** au lieu
  de `$fetch` : au SSR il rejoue les en-têtes de la requête (cookie de session) ; côté client c'est
  un `$fetch` normal.

## Décisions

- **`useRequestFetch()`** est le fetcher Nuxt prévu pour ce cas (SSR + cookies) ; `useFetch`
  l'utilise en interne, ce qui explique que les anciens composables ne souffraient pas du bug.

## Critères d'acceptation

- [x] `/`, `/vivier`, `/candidats`, `/utilisateurs`, `/jarvi` chargent au SSR (appels 200, plus
  d'« error.unexpected »).
- [x] Type-check des 4 espaces vert.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Bug latent séparé, non corrigé ici** : les pages utilisent `$t('error.unexpected')`, or
  `error.unexpected` est une **branche** (`{ title, message }`), pas une feuille → intlify affiche la
  clé littérale. La bonne clé est `error.unexpected.message`. Pré-existant, ~10 fichiers (admin +
  base + entreprise) → à traiter à part.
