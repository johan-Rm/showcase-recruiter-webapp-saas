# TASK 151 — admin : corriger le rendu de `error.unexpected` (objet i18n → message)

- **Créée le** : 2026-07-27.
- **Statut** : ✅ implémenté le 2026-07-27 — type-check des 4 espaces vert, conventions vertes,
  vitest vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (correction i18n, espace admin).
- **Réf** : `webapp/base/i18n/locales/{fr,en,ar}.yaml` (clé `error.unexpected`).

## Contexte

Sept écrans admin affichaient leur alerte d'erreur par `{{ $t('error.unexpected') }}`. Or
`error.unexpected` n'est pas une chaîne mais un **objet** `{ title, message }` (dans les trois
locales). Résolue sur un nœud objet, `$t` ne rend pas la phrase attendue : l'utilisateur voit la
clé (ou rien d'exploitable) au lieu du message d'erreur.

À ne pas confondre avec `inscription.error.unexpected`, qui est bien une chaîne — les écrans
d'inscription (`base/`) n'étaient pas touchés.

## Livré

Les sept alertes pointent désormais sur la feuille `error.unexpected.message` (la phrase courte,
adaptée à une alerte inline). Le `title` reste disponible pour un futur écran d'erreur pleine page.

## Décision

- **`.message` et non `.title`** : ces alertes sont des `<p>` inline sous le contenu ; le message
  est la phrase utile. Le titre ferait doublon avec le contexte déjà à l'écran.
- **Correction locale, pas de composant d'erreur partagé** : mutualiser l'affichage d'erreur est
  un autre sujet (couche présentation) — hors périmètre de ce correctif.

## Critères d'acceptation

- [x] Aucune occurrence nue de `$t('error.unexpected')` en admin ; toutes pointent sur `.message`.
- [x] Bug absent des autres espaces (candidat/entreprise/public) — vérifié, rien à corriger.
- [x] Type-check des 4 espaces vert ; conventions vertes ; vitest vert.
- [ ] `webapp-quality` complet — au passage PR.

## Fichiers

- (M) `webapp/spaces/admin/app/pages/index.vue`
- (M) `webapp/spaces/admin/app/pages/candidats.vue`
- (M) `webapp/spaces/admin/app/pages/candidats/[id].vue`
- (M) `webapp/spaces/admin/app/pages/vivier.vue`
- (M) `webapp/spaces/admin/app/pages/utilisateurs.vue`
- (M) `webapp/spaces/admin/app/pages/jarvi.vue`
- (M) `webapp/spaces/admin/app/pages/referentiels.vue`
