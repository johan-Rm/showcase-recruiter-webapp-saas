# TASK OPS-006 — le journal publié, et les maquettes dans un atelier

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29 — journal servi par le site dev-book, maquettes sorties du
  cadrage, brouillons désindexés.
- **Type** : Outillage — rangement du `dev-book/`, aucun changement applicatif.
- **Réf** :
  - task `OPS-004-charte-realignee-et-design-system-en-doc.md` (même démarche : sortir un document
    du cadrage quand son lectorat a changé)
  - `docs/dev-book/nuxt.config.ts`, `docs/Caddyfile.static`

## Contexte

Trois désordres sans rapport apparent, mais qui tenaient tous à un mauvais rangement du `dev-book/` :

- `journal/` était un dossier de premier rang, absent du site dev-book — six comptes rendus
  hebdomadaires que personne ne pouvait lire sans cloner le dépôt.
- `produit/` ne contenait qu'un `.gitkeep`.
- `cadrage/wireframes/` pesait **13 Mo pour 98 fichiers**, dont 32 itérations du seul écran
  cockpit, dans un dossier écrit pour le client.

## Décisions

### Le journal devient un sous-dossier de `tasks/`

Le journal raconte ce que les tâches ont produit — c'est le même sujet à une autre échelle. Le
placer sous `tasks/` le fait aussi entrer dans le lien symbolique `content/tasks` du site dev-book,
qui le sert sans configuration supplémentaire.

### Le prérendu devait être rouvert, sinon le déplacement ne servait à rien

Le site est servi **en statique** : `docs/Caddyfile.static` sert `.output/public`, une route non
prérendue n'existe pas en production. Or `nuxt.config.ts` ignorait `/tasks/` au prérendu. Un build
de contrôle avant toute correction l'a confirmé : **une seule page HTML générée**, l'accueil. Le
lien « Tâches » de la page d'accueil était mort depuis l'origine, sans que rien ne le signale.

Nitro accepte des fonctions comme motif d'exclusion, ce qui rend l'exception exprimable :

```ts
(path: string) => path.startsWith('/tasks/') && !path.startsWith('/tasks/journal')
```

Les 219 cartes de tâches restent hors du site, le journal passe. Le choix de garder les cartes hors
ligne n'est pas tranché ici : il l'était avant, cette tâche ne fait que cesser d'y enfermer le
journal.

### Le prérendu fonctionne par crawl, donc il faut un point d'entrée

Une page non liée n'est jamais découverte, même autorisée. D'où `tasks/journal/index.md`, qui sert
à la fois de page d'accueil du journal et de porte d'entrée du crawler, et le lien ajouté sur
l'accueil du site.

Contrepartie assumée : **ajouter une semaine demande une ligne dans cet index**. Nuxt Content v3 n'a
plus de composant de listing, et le site dev-book n'a aucune page Vue où interroger la collection.

### Les maquettes prennent un atelier à la racine

`ui-ux-design/` plutôt qu'un sous-dossier du `dev-book/` : ce n'est pas de la prose mais un atelier,
qui porte ses propres conventions de génération. Hors `cadrage/`, aussi, parce que ce sont des
artefacts de conception et non des documents remis au client.

Le nom écarte `design-system/`, qui aurait été la troisième chose du dépôt à porter ce nom — le
système, lui, vit dans `webapp/base/app/assets/css/` et se lit dans `docs/…/4.design/`.

### Seuls les écrans arbitrés entrent dans l'historique

`draft/` passe au `.gitignore`. Git n'oublie jamais : à 32 itérations pour un écran, et avec une
génération assistée qui va accélérer, l'historique portait une croissance sans plafond pour des
fichiers dont un seul compte.

Piège traité au passage : la « source de vérité UI », citée par quatre documents de cadrage, vivait
dans `draft/`. Ignorer le dossier sans la promouvoir d'abord l'aurait fait disparaître du dépôt pour
tout le monde. Elle est passée dans `validate/`, qui porte désormais trois écrans.

### La lane `espace/design`

Un atelier avec son rythme propre mérite sa branche : `espace/design`, préfixe `UX`. La branche
est créée et part de l'intégration. Son amont a été retiré — `git branch` depuis une référence
distante l'avait fait suivre `origin/develop`, ce qui invite à pousser au mauvais endroit.

## Vérification

- **Build réel du site dev-book, deux fois.** Avant correction : 1 page HTML. Après : 256 routes
  prérendues, **8 pages** — l'accueil, `/tasks/journal`, et les six semaines. Les 219 cartes de
  tâches restent absentes, comme voulu.
- **Contenu rendu**, pas seulement généré : `2026-s30.html` contient « 54 commits, 26 pull requests
  mergées », la page d'index porte les six liens, l'accueil pointe vers `/tasks/journal`.
- **Brouillons préservés** : 86 fichiers et 13 Mo toujours sur le disque après `git rm --cached`,
  vus comme ignorés par Git.
- **Références** : plus aucun chemin mort vers `cadrage/wireframes` hors cartes de tâches
  historiques ; les chemins cités par la doc existent sur disque.

## Livré

- `dev-book/journal/` → `dev-book/tasks/journal/` (+ `index.md`), servi par le site dev-book.
- `docs/dev-book/nuxt.config.ts` : exclusion de prérendu par fonction, et le commentaire qui dit
  pourquoi une route ignorée n'existe pas en production.
- `dev-book/cadrage/wireframes/` → `ui-ux-design/` ; `draft/` au `.gitignore`, `validate/` versionné.
- `ui-ux-design/CLAUDE.md` : conventions de génération, rituel de promotion, nommage.
- Lane `espace/design` (`UX`) au tableau de `git-workflow.md`, branche créée.
- `dev-book/produit/` retiré.
- Références repointées : quatre documents de cadrage, `AGENTS.md`, `dev-book/README.md`.

## Reste à faire (hors périmètre de cette tâche)

- **Le rôle Produit d'`AGENTS.md` n'a plus de périmètre.** Quatre mentions y désignent
  `dev-book/produit/**`, supprimé ici, et la tâche `081` vise ce dossier. À trancher : retirer le
  rôle, le repointer vers `cadrage/`, ou restaurer le dossier.
- **Le lien « Tâches » de l'accueil du dev-book reste mort** (`/tasks/001-scaffolding-api` n'est pas
  prérendu). Soit on publie les 219 cartes, soit on retire le lien.
- **Les 13 Mo de brouillons restent dans l'historique passé.** Le `.gitignore` borne la croissance,
  il ne réécrit rien. Un `filter-repo` serait le seul moyen de purger, au prix de tous les SHA.
- **Deux cartes de tâches** (`054`, `086`) citent l'ancien chemin des maquettes. Registre
  historique, laissé tel quel.
