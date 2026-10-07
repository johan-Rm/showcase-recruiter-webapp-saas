# TASK WEB-005 — socle : la marque retrouve son fer à gauche

- **Créée le** : 2026-07-30.
- **Statut** : ✅ implémenté le 2026-07-30 — `items-center` → `items-start`. **Aucune vérification
  exécutée** : capture et gates reportées à la PR, cf. « Vérification » ci-dessous.
- **Type** : fix — le code contredit la carte qui l'a décidé.
- **Tests** : sans objet — le diff n'ajoute aucune logique : une classe utilitaire, rien d'autre. La
  suite du webapp existe (fichiers `*.vitest.ts`) et couvre `services/` et `shared/`, du métier sans
  framework — ce que ce changement ne produit pas. **Exemption décidée par l'utilisateur** au
  cadrage, pas auto-accordée. Preuve par capture.

  > La première rédaction de cette ligne affirmait que le webapp ne portait **aucun** test. C'était
  > faux : le grep qui l'a établie cherchait `.spec.ts` et `.test.ts`, alors que la convention du
  > dépôt est `*.vitest.ts`. L'exemption tient, mais sur son vrai motif.
- **Réf** : `WEB-001` (la décision d'origine) · `LayoutBrand`
  (`webapp/base/app/components/layout/Brand.vue`) · aval : `ADM-010`.

## Contexte

`WEB-001` a décidé le 2026-07-28 que le badge d'espace passait **sous** le logo, **aligné à
gauche**, et sa section « Livré » annonce `inline-flex flex-col items-start gap-1.5`. Le commit qui
l'a livrée (`ef1e28c`) a écrit `items-center`. La décision et le code divergent depuis.

Ça se voit dans la sidebar admin, seul rail de navigation du produit : dans une colonne de 220 à
272 px, le logo et le badge sont centrés pendant que tous les libellés de navigation sont fer à
gauche. Le regard descend sur deux verticales au lieu d'une.

## Périmètre

- `LayoutBrand` : `items-center` → `items-start`.

## Hors périmètre

- **Le badge lui-même**, conservé — c'est `WEB-001` qui l'a placé là il y a deux jours.
- **La couleur du logo**, déjà réglée par `WEB-001` (`text-text`).
- **Le rideau de boot (`AppBootShell`) et les écrans auth/inscription**, que `WEB-001` avait déjà
  explicitement laissés de côté.
- **Tout le reste de la sidebar admin** — état actif, intitulés de groupe, filet séparateur : c'est
  `ADM-010`.

## Décisions de cadrage

- **Sur `espace/socle-webapp`, pas sur `espace/admin`.** Le composant est consommé par les quatre
  espaces ; le corriger est un travail fait *pour le socle lui-même*, et aucun espace ne le consomme
  en particulier. C'est le raisonnement de `WEB-001`, et le suivre garde une seule règle de
  rattachement pour un même fichier.
- **`WEB-001` n'est pas réécrite.** Sa carte reste le compte rendu de ce qui a été fait ce jour-là,
  erreur comprise. Celle-ci la cite et corrige le code.
- **Cette carte passe avant `ADM-010`.** Les deux sont indépendantes — aucune ne bloque l'autre —
  mais l'admin ne verra le résultat complet qu'après le merge du socle.

## Règles et cas limites

- **`items-start` est un alignement logique**, pas physique : en arabe (interface RTL), il suit le
  sens de lecture sans réglage supplémentaire.
- **Trois autres espaces rendent `LayoutBrand`** dans des contextes différents (cockpit entreprise,
  en-tête public, espace candidat). Vérifier qu'aucun ne reposait sur le centrage avant de conclure.

## Documentation impactée

Aucune. La charte ne prescrit rien sur l'alignement du bloc de marque, et le README de
`base/app/components/` ne porte que le nommage et le marqueur de racine — tous deux inchangés.

## Livré

- `webapp/base/app/components/layout/Brand.vue` : `items-center` → `items-start` sur la racine
  `layout-brand`. Une seule classe, aucun autre changement — le marqueur de racine reste en première
  position et aucun bloc `<style>` n'est introduit, conformément au README de
  `base/app/components/`.

## Vérification

**Aucune.** Le changement est commité sans preuve fraîche, et c'est une décision explicite de
l'utilisateur prise au moment du commit : la stack `socle-webapp` (`showcase-recruiter-webapp-saas-socle-webapp`, proxy
8680) n'a jamais été construite, et la démarrer demandait un premier build complet pour un
changement d'une classe. Les captures et les gates sont donc reportées à l'ouverture de la PR.

Ce que cela laisse ouvert, nommément : l'alignement n'est constaté dans **aucun** des quatre points
de rendu de `LayoutBrand`, et rien ne confirme encore qu'aucun d'eux ne reposait sur le centrage.

## Reste à faire (hors périmètre)

- **Vérification visuelle des quatre points de rendu**, à faire au passage PR : sidebar admin
  (`AdminSidebarNav`), cockpit et menu de compte entreprise (`cockpit.vue`, `AccountMenu`), en-tête
  public et candidat (`AppHeader`) — thème clair et thème sombre.
- **Dette groupée** : `WEB-001`, `ADM-005` et `ADM-009` portent chacune une vérification visuelle
  restée en attente sur `:8480`. Celle-ci est la quatrième — elles se traitent en un seul passage.

## Définition de terminé

- [ ] Dans la sidebar admin, logo, badge et libellés de navigation s'appuient sur le même axe.
- [ ] Les trois autres espaces qui rendent `LayoutBrand` vérifiés — aucun ne se dégrade.
- [ ] Capture à l'appui, thème clair et thème sombre.
- [ ] Conventions, lint, format, type-check des 4 espaces verts.

**Aucune case n'est cochée**, et ce n'est pas un oubli : les quatre demandent soit une instance qui
tourne, soit les gates, toutes deux reportées à la PR. La tâche est **codée**, pas **vérifiée**.
