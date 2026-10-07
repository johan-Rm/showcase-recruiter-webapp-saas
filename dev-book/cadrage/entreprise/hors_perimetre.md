# showcase-recruiter-webapp-saas — Espace Entreprise · Hors périmètre

> **Le seul document du dossier qui parle de ce qui n'existe pas.** Il est là pour une raison précise :
> plusieurs de ces notions **survivent dans le code** (entités, champs, routes). Sans cette note, un
> développeur — ou une IA — qui lit `CandidateAssignment` suppose que l'assignation est une feature à
> câbler.
>
> Les autres documents du dossier énoncent **ce qui est défini**. Celui-ci énonce **ce qu'il ne faut pas
> reconstruire**. Le nettoyage du code correspondant est porté par la tâche
> [`078`](../../tasks/078-api-retrait-notions-revoquees.md).

## Hors périmètre — ni V1 ni V2

| Notion | Trace dans le code | Ce qui la remplace |
| --- | --- | --- |
| **Assignation** d'un candidat à un recruteur | `CandidateAssignment`, `AssignmentProcessor`, routes, voters | rien — le dévoilement est déjà partagé par toute l'entreprise |
| **Écarter** un profil | `discarded` (si implémenté) | **Favoriser** joue le tri positif ; il n'y a pas de tri négatif |
| **Prétentions salariales** | `Candidate.salaryExpected`, `salaryMin` — **restent en base**, ne sont **ni exposées ni filtrables** | rien |
| **Fraîcheur du profil** (« entré dans le vivier depuis < 7 j ») | — | les **nouveautés d'une recherche** : les profils qui ont **rejoint son jeu de résultats** depuis ma dernière visite ([criteres §9](./criteres_de_filtrage_profils.md)) |
| **Short list partagée `Organization`** | `Favorite` (task 008) — **à refondre** | **favoris** et **shortlist**, deux listes distinctes **portées par la recherche** ([les_2_outils §4](./les_2_outils.md)) |
| Outil **« Le Rédacteur »** (diffusion, messagerie, notes) | — | l'espace entreprise a **deux outils** : la Table et la Visionneuse |
| Routes **`/favoris`** et **`/devoiles`** | — | des **tabs d'état** de la Table, dans le périmètre de la recherche |

## Notions d'UI à ne pas reconstruire

**Barre d'état** · **Mode Focus** · **Panneau contextuel** · modes **Normal / Sélection** · **grille de
cartes** comme vue produit · **colonne et tab « Vérifiés »** *(le badge et la facette de recherche, eux,
existent)* · **tri par colonne** · **anneau de qualification pondéré** *(remplacé par un ratio brut)*.

Le shell est décrit dans [ui/application_shell.md](./ui/application_shell.md) ; s'il n'y figure pas,
il n'existe pas.

## Règle

**Aucune de ces notions ne revient sans décision produit explicite.** Si le besoin remonte du terrain,
il se rediscute — mais on ne le recâble pas au motif qu'un vestige traîne dans le modèle.
