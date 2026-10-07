# TASK OPS-010 — l'atelier de maquettes n'a plus que deux statuts

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — la racine de `ui-ux-design/` est vidée, les essais de palette
  redescendus en `draft/`, un mockup cockpit promu en `validate/`.
- **Type** : Refactor — réorganisation du socle documentaire ; aucun code applicatif touché.
- **Tests** : sans objet — la tâche ne déplace que des maquettes HTML autoportantes et un fichier
  de conventions. Aucun code exécutable, donc rien à couvrir.
- **Réf** :
  - task `OPS-006` (création de l'atelier `ui-ux-design/` et de la règle `draft/` gitignoré)
  - `ui-ux-design/CLAUDE.md`

## Contexte

`OPS-006` a créé l'atelier avec **trois** emplacements : `draft/` ignoré, `validate/` versionné, et
une racine versionnée qui portait les cinq nuanciers d'ambiance et les quatre fiches d'essai.

Ce troisième statut n'a jamais eu de définition. Une fiche d'essai n'est ni un brouillon qu'on jette
ni un écran qui fait foi : elle est restée à la racine faute d'endroit, pas par décision. Et
l'ambiance retenue — Cèdre — ne se lit pas dans ces essais mais dans la charte, qui porte le
nuancier réel depuis `OPS-004`.

## Périmètre

- Les 9 fichiers de la racine descendent dans `draft/`, donc **hors du dépôt**.
- `mockup-espace-entreprise-vue-cockpit-v23.html` est promu de `draft/` vers `validate/`.
- `ui-ux-design/CLAUDE.md` décrit les deux statuts restants.

## Hors périmètre

- **Départager les deux mockups cockpit** de `validate/` — voir « Reste à faire ».
- Le tri des 86 autres brouillons de `draft/`.

## Décisions de cadrage

### Deux statuts, pas trois

Un fichier de l'atelier est soit un brouillon qui s'ignore, soit une référence qui fait foi. Le
troisième cas n'existait que par défaut d'arbitrage, et un statut sans définition finit par accueillir
tout ce qu'on ne sait pas classer.

Les essais de palette sont des explorations : ils descendent en `draft/`. Ils restent sur les disques
où ils sont, l'historique les conserve, mais un clone frais ne les aura plus — c'est le sens même de
`draft/`.

### `.gitignore` ne désuit rien — le piège de ce déplacement

Un `git mv` vers `draft/` aurait laissé les 9 fichiers **versionnés à une nouvelle adresse** :
`.gitignore` n'a d'effet que sur les fichiers non suivis. Il a fallu un `git rm --cached` derrière
pour que la règle du dossier s'applique réellement.

C'est vérifiable et ça se reproduira à chaque descente de `validate/` vers `draft/` : la règle est
désormais écrite dans `CLAUDE.md`.

### La promotion inverse ne peut pas être un `git mv`

`v23` vivait dans `draft/`, donc **non suivi** : `git mv` refuse une source qu'il ne connaît pas.
C'est un `mv` puis un `git add`, et Git l'enregistre comme un ajout — pas comme un renommage. Un
fichier promu n'a pas de passé dans le dépôt, et c'est normal.

## Règles et cas limites

- Les 9 fichiers **quittent le dépôt** : ils restent sur disque, l'historique les garde, un clone
  frais ne les a plus.
- Les deux mockups promus portaient des chemins internes périmés — l'un de deux déménagements
  (`dev-book/cadrage/wireframes/`), l'autre d'un seul. Corrigés : un fichier versionné ne doit pas
  pointer vers une adresse morte.
- Trois mentions subsistent dans les commentaires CSS de `cockpit-latest.html`
  (`(color-palette-emeraude.html)`) : elles nomment une palette, elles ne pointent pas un chemin.

## Documentation impactée

- `ui-ux-design/CLAUDE.md` — l'arborescence, et la ligne qui disait ce qu'un commit peut porter.
- Aucune page de `docs/showcase-recruiter-webapp-saas/` : vérifié, aucune n'affirme que les essais de palette sont
  versionnés.

## Vérification

- **Après le `git mv` seul**, `git ls-files ui-ux-design/draft` retournait **9** — les fichiers
  étaient toujours suivis. Après `git rm --cached`, il retourne **0**, et `git status --ignored`
  voit `ui-ux-design/draft/` comme ignoré.
- **Suivi restant dans `ui-ux-design/`** : `CLAUDE.md` et les 4 écrans de `validate/`, rien d'autre.
- **Les 9 fichiers sont bien sur le disque** dans `draft/`, et quatre d'entre eux ont été comparés
  octet à octet à leur version versionnée — identiques.
- **Chemins internes** : plus aucune référence à `dev-book/cadrage/wireframes/` ni à
  `ui-ux-design/color-palette-*.html` dans les fichiers de `validate/`.

## Livré

- `ui-ux-design/color-palette-{argile,emeraude,marine,olive,sable}.html` (D) — descendues en
  `draft/`, désindexées.
- `ui-ux-design/fiche-essai-palette-n{0,1,3,4}.html` (D) — idem.
- `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` (+) — promu depuis `draft/`.
- `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html` (M) — chemin interne.
- `ui-ux-design/CLAUDE.md` (M) — deux statuts, et la règle du `rm --cached`.

## Reste à faire (hors périmètre de cette tâche)

- **`validate/` porte deux mockups cockpit** — `latest` et `v23` — sans que rien ne dise lequel fait
  foi. Or **dix documents de cadrage** désignent `cockpit-latest.html` comme « source de vérité
  UI ». Écarté volontairement de cette tâche ; à trancher entre remplacement, repointage des dix
  documents, ou coexistence documentée dans `CLAUDE.md`.
- **Les 86 autres brouillons de `draft/`** n'ont pas été triés.

## Définition de terminé

- [x] La racine de `ui-ux-design/` ne porte plus que `CLAUDE.md`.
- [x] Les 9 fichiers sont dans `draft/`, sur disque et hors du dépôt.
- [x] `v23` est dans `validate/` et suivi.
- [x] Aucun fichier versionné de l'atelier ne pointe vers une adresse morte.
- [x] `CLAUDE.md` décrit l'arborescence réelle.
