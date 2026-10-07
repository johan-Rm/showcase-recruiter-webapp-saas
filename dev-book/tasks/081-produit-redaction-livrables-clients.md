# TASK 081 — Produit : rédiger les livrables clients (`dev-book/produit/`)

- **Créée le** : 2026-07-13.
- **Statut** : ⬜ à faire.
- **Type** : rédaction fonctionnelle.
- **Agent** : **MOA** (cf. [`CLAUDE.md`](../../CLAUDE.md)).
- **Réf ClickUp** : _à créer_.

## Contexte

Les documents de `dev-book/cadrage/` sont **exportés et lus par les clients et les collaborateurs**.
Ils sont aujourd'hui écrits pour des développeurs : noms d'entités, champs, routes, notations
ensemblistes, tokens de couleur, statuts de version, historique des décisions. Un décideur ne peut pas
les lire.

`dev-book/produit/` est le dossier des **livrables**. Le cadrage reste la **matière première** (brief,
charte, maquettes, règles métier) : le MOA la lit, il ne l'exporte pas.

## Objectif

Un jeu de documents qu'un client, un investisseur ou un collaborateur non technique lit **seul, sans
poser de question**.

## Règle d'or

> **Si un lecteur non technique ne comprend pas la phrase, la phrase est fausse.**

## Ce qu'un livrable ne contient jamais

- aucune note (« à cadrer », « point de vigilance ») ;
- aucun TODO, aucune question ouverte ;
- aucune notion de version (V1 / V2) ni de statut d'avancement ;
- aucune date, aucun historique de décision ;
- rien qui n'existe pas ou n'existe plus ;
- **aucun jargon d'implémentation** : ni nom d'entité, ni champ, ni route, ni fichier, ni notation
  ensembliste, ni token de couleur.

Un livrable décrit **le produit tel qu'il est, au présent**, à quelqu'un qui va s'en servir ou le
vendre.

## Périmètre

Un document par espace, écrit du point de vue de celui qui l'utilise :

- **L'espace entreprise** — la promesse, les deux outils (trouver, choisir), le dévoilement et ce
  qu'il débloque, la shortlist et son export, l'équipe et les crédits.
- **L'espace candidat** — s'inscrire, se rendre visible, déclarer sa présence au Maroc, garder la
  main sur ses données.
- **L'espace d'administration** — valider une entreprise, suivre l'activité.
- **Le site public** — ce que le visiteur comprend avant de créer un compte.

## Ce qui fait la valeur du travail

Le produit se raconte par **ses écrans et ses gestes**, pas par ses tables. Le différenciant tient en
une phrase qu'un client doit reconnaître immédiatement : *trouver un profil qualifié, basé en Europe,
prêt à rentrer — et physiquement au Maroc au bon moment.* Tout le reste en découle.

## Invariants

- **Traduire n'est pas arbitrer** : aucune décision produit ne change en passant du cadrage au
  livrable. En cas de doute sur le fond, le MOA demande.
- Le **vocabulaire est stable** d'un document à l'autre : un même geste porte toujours le même nom.
- Ce qui est technique, versionné, daté ou à faire **n'est pas supprimé** : il est **rangé** dans
  `dev-book/tasks/`, chez son lecteur.

## Critères d'acceptation

- [ ] Un lecteur non technique comprend chaque document sans poser de question.
- [ ] Aucun des interdits ci-dessus n'y figure.
- [ ] Le vocabulaire est cohérent entre les documents.
- [ ] Aucune décision produit n'a changé au passage.

## Hors périmètre

- Les maquettes, la charte et le brief restent dans `dev-book/cadrage/` : ce sont des sources, pas
  des livrables.
- Les règles serveur et les critères mappés sur la base de données ne se traduisent pas : ils
  s'adressent aux développeurs et vivent dans `docs/` et `dev-book/tasks/`.
