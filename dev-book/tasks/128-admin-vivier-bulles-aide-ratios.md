# TASK 128 — admin : bulles d'aide et ratios sur l'écran « Santé du vivier »

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25 — front seul (webapp admin + base), aucun changement d'API.
- **Type** : Front (webapp `spaces/admin` + `base`).
- **Placement** : enrichit l'écran `/vivier` livré par `119` — mêmes chiffres, même source
  (`QualificationReporter`). Aucun nouvel endpoint, aucun changement de contrat.
- **Lié à** : `119` (l'écran enrichi), `118` (la reconnaissance partielle que « rattachés à
  l'identique » rend lisible).
- **Réf** :
  - `api/src/Domain/Reporting/QualificationReporter.php` (les libellés source, couplage exact)
  - `webapp/spaces/admin/app/pages/vivier.vue` (l'écran)

## Livré

- `UiInfoHint` (`base/app/components/ui/InfoHint.vue`) : bulle d'aide accessible — s'ouvre au
  survol, au focus clavier **et** au tap (jamais hover-only), `aria-describedby`, fermeture sur
  Échap ; icône SVG inline, zéro dépendance.
- Registre front `spaces/admin/app/utils/vivierMetrics.ts` : `libellé back → { définition, clé de
  ratio }` pour les 27 métriques du snapshot, plus le résolveur de dénominateur.
- `vivier.vue` : chaque métrique connue porte sa bulle ⓘ, et affiche « valeur / population de
  référence » (ex. `2 205 / 2 557`) là où le ratio a du sens.
- Clé i18n `a11y.moreInfo` (fr / en / ar) pour le nom accessible du bouton.

## Contexte

Les libellés du snapshot sont des termes-métier **non devinables** : « Postes du parcours » n'est
pas « Expérience métier », « dont rattachés à l'identique » ou « Métier reconnu » demandent une
définition. Et un compte brut ne dit pas la **couverture** : « Ancienneté renseignée : 2 205 » —
sur combien ? Le lecteur doit pouvoir situer chaque chiffre sans quitter l'écran ni relire le code.

## Objectif

Rendre chaque métrique **auto-explicative** (définition au survol/focus) et **située** (ratio sur
la bonne population), sans toucher au back ni au contrat.

## Décisions

- **Définitions en français assumé**, dans un registre front — pas d'i18n : les libellés sont émis
  par le back en français et **ne sont pas traduits**, une définition traduite à côté d'un libellé
  français serait incohérente. Seul le nom accessible du bouton (`a11y.moreInfo`) est traduit.
- **Trois dénominateurs**, chacun une autre métrique du snapshot (jamais un chiffre en dur) :
  `actifs` → Profils actifs · `postes` → Postes du parcours · `rattaches` → postes rattachés.
- **Dégradation propre** : un libellé absent du registre n'a ni bulle ni ratio, jamais d'erreur.
- **Couplage vérifié** : les clés du registre doivent matcher **exactement** les libellés du back
  (apostrophe typographique, cadratin) — vérifié par comparaison programmatique (27/27, 0 écart).

## Critères d'acceptation

- [x] Chaque métrique connue affiche sa définition au survol, au focus clavier et au tap.
- [x] Ratios `/2 557`, `/14 895`, `/9 874` là où ils ont du sens ; rien ailleurs.
- [x] Aucun changement d'API (même `QualificationReporter`, même OpenAPI).
- [x] `webapp-quality` vert (lint, format, type-check des 4 espaces).

## Points de vigilance

- **Auto-import du nouveau composant** : `UiInfoHint` est pris par le dev server via HMR ; un
  nouveau dossier `app/utils` peut demander un restart — d'où l'import **relatif explicite** du
  registre dans `vivier.vue`.
- **Accessibilité mobile** : pas de bulle hover-only ; à surveiller, le débordement du popover sur
  la colonne de droite en grille 2 colonnes (pas de lib de positionnement, choix KISS).
