# TASK 071 — webapp : page d'erreur (404 / 500)

- **Créée le** : 2026-07-12.
- **Statut** : ✅ terminé le 2026-07-12.
- **Type** : Front — Webapp Nuxt (socle).
- **Dépend de** : tâches `051` (tokens) · `053` (i18n) · `070` (kit `ui/`).
- **Réf** : `webapp/base/app/error.vue`.

## Contexte

La webapp n'avait **aucune page d'erreur** : une URL invalide tombait sur l'écran par défaut de
Nuxt — hors charte, hors langue, sans issue pour l'utilisateur. Trou du socle, sans tâche.

## Livré

`base/app/error.vue` (dans le socle, donc partagé par les 4 espaces) :

- distingue **404** (« introuvable ») du reste (« une erreur est survenue ») ;
- **traduite FR / EN / AR** (clés `error.*`), conformément à la règle « aucun texte en dur » ;
- utilise le **kit `ui/`** (`UiCard`, `UiButton`) et les tokens brand ;
- enveloppée dans `<NuxtLayout>` : l'utilisateur **garde le header** (marque, langue, thème,
  déconnexion) au lieu d'être éjecté sur une page nue ;
- bouton de retour via `clearError({ redirect })` — sans `clearError`, l'état d'erreur **survit à
  la navigation** et la page d'erreur reste collée.

Le **détail technique de l'erreur n'est jamais affiché** : il fuiterait l'interne en production,
et le développeur a déjà l'overlay Nuxt.

## Vérifié

404 rendu dans les 3 langues (« Page introuvable » / « Page not found » / « الصفحة غير موجودة »),
code affiché, header présent, `quality-check` vert (conventions incluses).

Note : en HTTP, Nitro ne rend la page d'erreur **que si la requête accepte le HTML**
(`Accept: text/html`). Sans cet en-tête — le cas de `curl` par défaut — il répond en **JSON**.
C'est le comportement attendu, pas un défaut.

## Reste

Une erreur survenue **pendant le rendu du layout** ferait boucler `<NuxtLayout>`. Cas limite non
traité : à surveiller si un jour le layout dépend d'un appel réseau.
