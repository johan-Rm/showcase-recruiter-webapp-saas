# TASK 154 — API : la shortlist disparaît, l'export porte sur la sélection

> Numérotée `151` au moment du commit, renumérotée en `154` : une autre lane avait pris
> le même numéro en parallèle. Les messages de commit mergés citent donc encore « task 154 ».

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — contrat régénéré, types du front en phase.
- **Type** : API — surface de contrat et modèle.
- **Réf** : task 073 (les deux listes), task 077 (l'export CSV), task 150 (le retrait côté écrans).

## Contexte

Le produit n'a plus deux listes. On retient ses **top profils en favoris** dans la Visionneuse, et
c'est la **Table** qui dévoile puis qui exporte — à l'unité ou en lot.

La shortlist était pourtant un sous-système complet : une entité, quatre endpoints, un writer CSV, un
journal d'export, et l'invariant `shortlist ⊆ dévoilés` gardé à l'écriture.

## Décisions

### L'export prend une SÉLECTION

`POST /v1/searches/{searchId}/export` reçoit `candidateIds` — une ligne ou un lot. Le CSV, le journal
et l'accusé RGPD sont conservés tels quels : ils ne dépendaient pas de la shortlist, seulement de
« des profils sortent de la plateforme ».

**On n'exporte jamais « tout » implicitement.** Une sélection vide est refusée : ce qui sort est ce
qu'on a désigné.

### Deux gardes, et un refus qui nomme

La sélection doit appartenir au **jeu de résultats** de la recherche. Sans ce contrôle, l'export
deviendrait une porte de sortie universelle : il suffirait de poster n'importe quel identifiant de
candidat sur une recherche qu'on possède.

Et chaque profil doit être **dévoilé** — une ligne sans coordonnées n'a rien à faire dans un fichier
qu'on emporte. Dans les deux cas l'API **refuse en nommant** les profils fautifs, plutôt que de
filtrer en silence : un fichier amputé ne se remarque pas.

### Ce qui disparaît, ce qui est recyclé

| Disparaît | Recyclé sous un nouveau nom |
| --- | --- |
| `Shortlist` (entité, POST/GET/DELETE) | `ShortlistExport` → `SearchExport` (le journal) |
| `ShortlistInput`, `ShortlistOutput` | `ShortlistCsvWriter` → `SearchCsvWriter` |
| `ShortlistInvariantViolationException` | `ShortlistExportHandler` → `SearchExportHandler` |
| `Domain\Search\CandidateNotRevealedException` | `ShortlistExportInput` → `SearchExportInput` |
| `isShortlisted` sur la carte candidat | |

`SearchListWriter` ne garde que les favoris — et perd sa dépendance au dévoilement, qui n'existait
que pour l'invariant de la shortlist.

Le fichier exporté s'appelle désormais `profils-<recherche>.csv`.

## Vérification

`SearchExportTest`, `SearchListsTest`, `SearchTest` : 27 tests, 200 assertions. phpstan, php-cs-fixer,
deptrac et `lint:container` verts. `api/openapi.json` régénéré (−259 lignes) et
`webapp/shared/types/api.ts` avec lui, dans le même commit — la gate `quality-contracts-check` les
compare.

## Reste à faire (hors périmètre de cette tâche)

- **Front** : brancher l'export sur la sélection de la Table (il n'y a pas encore de sélection), et
  retirer `isShortlisted` du type hand-authored `candidate-card.ts`.
- **Cadrage** : `vue_la_shortlist.md` et la vingtaine de documents qui s'y réfèrent.
- **Dévoilement en trois types** (contact / CV / compte rendu, 1 crédit chacun) : tâche suivante.
