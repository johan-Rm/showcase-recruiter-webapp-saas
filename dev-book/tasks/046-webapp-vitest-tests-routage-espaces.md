# TASK 046 — webapp : Vitest + tests du routage par espace

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire — **à réécrire** : le routage qu'elle testait n'existe plus (voir « Mise à jour »).
- **Type** : Front — Webapp Nuxt (qualité, filet de sécurité).
- **Dépend de** : tâche `047` (nommage des scripts qualité) pour l'intégration à `quality:check`.
- **Réf** : `webapp/shared/utils/space.ts` · `webapp/app/middleware/space.global.ts` ·
  `.github/workflows/` (gate `webapp-quality`).

## Contexte

La webapp n'a **aucun outil de test installé**. `quality-check` se limite à lint + format +
type-check. Or la résolution host → espace (`resolveSpace`) et le middleware global qui en
dépend constituent le **garde-frontière des trois espaces authentifiés** : une régression
silencieuse y exposerait un espace SaaS. C'est exactement le « cœur sensible » que la doctrine
projet (TDD ciblé) demande de couvrir.

Le projet de référence `blue-bay-mogador` (hors repo) colocalise ses tests unitaires à côté du
code sous la convention `*.vitest.ts` — on la reprend.

## Objectif

Poser Vitest et couvrir la résolution d'espace et le garde d'accès.

## Périmètre

- Installer `vitest` (+ `@nuxt/test-utils` si le middleware l'exige) et un `vitest.config.ts`.
- Convention : fichiers `*.vitest.ts` **colocalisés** avec le code testé, pas de dossier
  `tests/` séparé.
- `shared/utils/space.ts` → `shared/utils/space.vitest.ts`. Cas à couvrir :
  - apex exact (`baseDomain`) → `public` ;
  - `candidat.` / `entreprise.` / `admin.` → l'espace correspondant ;
  - sous-domaine inconnu (`www.`, `foo.`) → `public` ;
  - host avec port (`entreprise.localhost:3000`) → l'espace, port ignoré ;
  - host hors `baseDomain` (`evil.com`, `xentreprise.localhost`) → `public` ;
  - host vide.
- `app/middleware/space.global.ts` → tests du garde : `definePageMeta({ spaces })` respecté,
  espace SaaS sans session → redirection `/login`, session au mauvais rôle → refus, page hors
  espace → 404.

## Invariants

- Un espace SaaS n'est jamais servi sans session au rôle correspondant.
- Aucune donnée candidat réelle dans les fixtures.

## Critères d'acceptation

- [ ] `bun run test` vert, tous les cas ci-dessus couverts.
- [ ] Les tests tournent dans la CI `webapp-quality`.
- [ ] `quality:check` enchaîne lint + format + type-check + test (cf. tâche `047`).
- [ ] Un `resolveSpace` volontairement cassé fait échouer la suite (le filet mord).

## Hors périmètre

- **Playwright / E2E** : écarté explicitement (décision du 2026-07-09). À rouvrir quand un
  parcours utilisateur réel existera.
- Tests de composants Vue.

## Mise à jour 2026-07-11 — à réécrire

Cette tâche visait à tester `resolveSpace()` (host → espace) et le middleware
`space.global.ts`. **Ces deux artefacts n'existent plus** : la migration en Nuxt Layers (`022`)
a supprimé la résolution par host — l'espace est figé au build et l'existence d'une page est
structurelle (404 natif, sans code de garde).

Le filet de sécurité reste **pertinent**, mais sur d'autres cibles :

- `SPACE_ROLE_PREFIX` + la garde d'auth (`base/app/middleware/auth.global.ts`) — c'est
  désormais **le** garde-frontière des espaces (session + rôle) ;
- l'isolation des builds : une page d'un espace ne doit pas exister dans les autres.

Périmètre à reformuler avant de prendre la tâche.
