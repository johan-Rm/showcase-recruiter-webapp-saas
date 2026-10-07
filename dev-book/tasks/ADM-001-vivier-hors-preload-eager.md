# TASK ADM-001 — webapp : sortir le vivier du préchargement au repos (loader `eager`)

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (système de chargement, `base/` mécanisme + `spaces/admin` réglage).
- **Réf** : `useAppBootstrap.preloadAll` (carte 139) · `AdminVivierProvider` / `QualificationReporter`
  côté API.

## Contexte

Au chargement de n'importe quelle page admin, `preloadAll` (anticipation au repos, carte 139)
précharge **tous** les domaines en parallèle. Mesuré en console : `GET /api/admin/vivier` à
**~1780 ms** — de loin le plus lent des six, et il **étrangle** les autres appels parallèles.

Cause côté API : le vivier agrège l'état de la base qualifiée via `QualificationReporter`
(`snapshot` + `distributions` + `fillRate`) — **~29 requêtes**, dont plusieurs déroulent du JSON en
plein-table (`json_array_elements` sur `candidate_jarvi`). Lourd **par nature**, et recalculé à
chaque chargement alors que la donnée ne bouge qu'à l'ingestion quotidienne.

## Livré

- **`BootstrapLoader.eager?: boolean`** (défaut `true`) — un loader peut se déclarer **hors du
  preload au repos**. `preloadAll` saute les `eager: false` ; ils restent **critiques à la
  navigation** (chargés quand on va réellement sur leur route).
- **`loadVivier` → `eager: false`** : plus de vivier au chargement des autres pages. Il reste
  critique sur `/vivier` (la vue) et `/candidats` (taux de remplissage rendu au SSR).

## Décisions

- **Un drapeau sur le loader**, pas une liste d'exclusion à part : la propriété « lourd, à ne pas
  anticiper » vit avec le loader, là où on la lit.
- **Hiérarchie dans l'anticipation** : léger = préchargé eager, lourd/rare = différé à la nav. Le
  preload reste la règle ; `eager: false` est l'exception motivée.

## Critères d'acceptation

- [x] Landing sur `/`, `/utilisateurs`, etc. → **aucun** appel `/api/admin/vivier`.
- [x] `/vivier` et `/candidats` chargent toujours le vivier (critique, SSR sur accès direct).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Suite (hors périmètre)

- **Prefetch au survol** du lien Vivier → clic instantané sans coût au repos (enhancement optionnel).
- **Cache/précalcul serveur** du snapshot vivier (admin-wide, invalidé à l'ingestion) → l'endpoint
  lit 1 ligne au lieu de 29 requêtes. **Le vrai fix de fond**, à faire sur `espace/api`.
