# TASK OPS-013 — la carte des domaines rejoint la doc, et la roadmap sort du cadrage

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — diagramme dans `architecture.md`, carte servie en statique,
  roadmap remontée d'un cran.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche déplace des documents et ajoute un diagramme Markdown.
- **Réf** :
  - task `OPS-012` (les sources reçues sortent du cadrage)
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` § Sous-domaines

## Contexte

`dev-book/cadrage/` portait deux fichiers qui n'y avaient plus leur place une fois le dossier réduit
à ce que l'équipe écrit sur le produit :

- **`carte-domaines.html`**, une carte radiale des huit sous-domaines — de la documentation
  technique déguisée en document de cadrage ;
- **`roadmap.md`**, les phases P0→P6 et leurs jalons — le plan de l'équipe, pas du cadrage produit.

## Périmètre

- La carte est servie par le site de doc et doublée d'un diagramme Mermaid dans la section qui
  possède déjà l'information.
- `carte-domaines-v1.html` supprimée.
- La roadmap remonte à `dev-book/roadmap.md`, ses références repointées.

## Hors périmètre

- La suite de la réduction de `dev-book/cadrage/`.
- Le libellé « showcase-recruiter-webapp-saas » dans l'en-tête de la carte, resté en deux mots.

## Décisions de cadrage

### Le diagramme, pas seulement le fichier

`architecture.md` documentait **déjà** les huit sous-domaines dans un tableau. Y déposer la carte
sans plus aurait créé un second propriétaire pour la même information, et le jour où un sous-domaine
change, l'un des deux ment.

Le tableau garde donc la propriété ; le Mermaid lui ajoute la vue d'ensemble — le message de la
carte, « un domaine, plusieurs portes d'entrée », que sept lignes de tableau ne donnent pas.

**Le site ne rend pas Mermaid**, et le build l'a montré : aucun module n'est installé, le bloc
ressort coloré par Shiki comme du code. L'ERD de `data-model.md` subit exactement le même sort
depuis toujours. Le diagramme reste donc lisible en source — la syntaxe se lit à l'œil — et c'est
la carte HTML liste juste en dessous qui porte l'image.

C'est cohérent avec l'existant plutôt que satisfaisant. Installer un rendu Mermaid pour le site
profiterait aux deux diagrammes d'un coup ; c'est une tâche à part, notée en aval.

### Le HTML reste, en statique

Le dessin porte une lecture que le Mermaid n'a pas — familles, icônes, orbite. Il est servi depuis
`docs/showcase-recruiter-webapp-saas/public/`, donc à `/carte-domaines.html`, et lié depuis la section.

**Pas dans `content/`** : Nuxt Content y rend du Markdown, un `.html` y serait un fichier mort
qu'aucune route ne sert.

### Les polices distantes sautent

La carte chargeait Cormorant Garamond et DM Sans depuis Google Fonts. Deux défauts pour un document
servi en statique : la page se dégrade hors ligne, et elle émet une requête vers un tiers à chaque
affichage — ce que le projet s'interdit ailleurs (`webapp/nuxt.config.ts`, `ui.fonts: false`, pour
garder Sora et Geist auto-hébergées).

Les piles passent à `'Sora'` et `'Geist'` avec les replis d'origine conservés. La mise en page ne
bouge pas ; là où les fontes ne sont pas installées, la pile système prend le relais.

Au passage, ce duo était la typographie **abandonnée** de l'ancienne direction.

### La roadmap n'est pas une source reçue

Elle a été proposée pour `requirements/`, dont le README pose la règle : « ce que le projet a reçu…
on cite un fichier d'ici, on ne le corrige pas ». La roadmap échoue aux deux critères — personne ne
l'a envoyée, et elle est faite pour être corrigée.

Elle remonte donc à `dev-book/roadmap.md` : le livre de l'équipe porte les unités dans `tasks/`, les
procédures dans `runbooks/`, et désormais leur ordre à sa racine.

## Règles et cas limites

- La carte garde ses couleurs d'origine, issues d'un nuancier abandonné. Assumé : c'est un schéma
  technique, pas une pièce de marque, et le repeindre était un autre chantier.
- Les cartes de tâches historiques qui citent `dev-book/cadrage/roadmap.md` sont laissées telles
  quelles.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` — diagramme, lien, et chemin de la roadmap.
- `CLAUDE.md` (×2), `dev-book/cadrage/todo.md` — chemin de la roadmap.

## Vérification

- **Build réel du site de doc** (`bun run build:showcase-recruiter-webapp-saas`, dépendances installées dans le
  worktree) : « Build complete ». `/carte-domaines.html` est présent dans `.output/public`
  (10 722 octets), et `architecture/architecture.html` porte le lien vers la carte ainsi que le
  texte du diagramme.
- **Le bloc Mermaid ne rend PAS un diagramme** : il ressort en `language-mermaid shiki`, c'est-à-dire
  du code coloré. Vérifié aussi sur `data-model.html`, dont l'ERD subit le même sort — le site n'a
  aucun module Mermaid. Constat, pas régression.
- **Chemins** : les quatre références à la roadmap sont repointées ; celui d'`architecture.md`, seul
  chemin relatif, a été résolu sur disque. Plus aucune occurrence de `cadrage/roadmap` hors cartes
  de tâches.
- **Autoportance de la carte** : zéro `http://` ou `https://` dans le fichier, zéro occurrence de
  `DM Sans` ou `Cormorant`.

## Livré

- `docs/showcase-recruiter-webapp-saas/public/carte-domaines.html` (R) — depuis `dev-book/cadrage/`, polices remplacées.
- `dev-book/cadrage/carte-domaines-v1.html` (D).
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` (M) — diagramme Mermaid, lien vers la
  carte, chemin de la roadmap.
- `dev-book/roadmap.md` (R) — depuis `dev-book/cadrage/`.
- `CLAUDE.md` (M), `dev-book/cadrage/todo.md` (M) — chemins repointés.

## Reste à faire (hors périmètre de cette tâche)

- **La réduction de `dev-book/cadrage/`** continue : il reste `traitements-profils.md`,
  `runbook-traitements-profils.md`, `vue-ensemble-plateforme.html`, `todo.md`, `draft/` et les
  dossiers par espace.
- **L'en-tête de la carte affiche « showcase-recruiter-webapp-saas »** en deux mots, alors que la marque est « showcase-recruiter-webapp-saas ».
- **La roadmap est périmée** : dernière modification le 25 juin, deux jalons déjà dépassés.
- **Le site de doc ne rend pas Mermaid.** Y installer un module ferait des deux diagrammes — celui-ci
  et l'ERD de `data-model.md` — de vraies figures au lieu de blocs de code.

## Définition de terminé

- [x] `architecture.md` porte le diagramme (en source) et le lien vers la carte.
- [x] La carte est servie à `/carte-domaines.html` et ne dépend d'aucun réseau.
- [x] `carte-domaines-v1.html` a disparu.
- [x] `dev-book/roadmap.md` existe et ses références résolvent.
