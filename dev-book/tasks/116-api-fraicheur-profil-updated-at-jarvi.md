# TASK 116 — API : fraîcheur du profil (persister l'`updatedAt` Jarvi)

- **Créée le** : 2026-07-22.
- **Statut** : ✅ **close le 2026-07-24** — la question « quelle est la fraîcheur d'un profil ? » a sa
  réponse : **aucun champ de date Jarvi ne la porte**. Le besoin est repris par `121` sous un autre
  angle (dernier contact). La colonne reste, sans filtre.
- **Type** : API — ingestion Jarvi / qualification à l'import.
- **Lié à** : `095` (qualifications dérivées), `062` (filtres conditionnés à la couverture),
  `061` (recherche), `092` (provenance par bloc).

## Contexte

**Aucune date ne dit l'âge d'un profil.** Ce qu'on a en base ne répond pas à la question :

- `synced_at` = la date à laquelle **nous** avons synchronisé (2 journées distinctes sur tout le
  vivier). Ça date notre import, pas la donnée.
- **CV : 60 profils sur 1 699 (3,5 %)** en portent un. Eux seuls ont un `parsedAt`.
- Historique d'activité (`historyEntries[].occurredAt`) : non vide sur **60 profils**.

La cause est structurelle : **1 668 profils sur 1 699 (98 %) viennent du sourcing LINKEDIN**, pas
d'une candidature. Il n'y a donc ni CV ni activité pour eux, et « CV mis à jour » n'a pas de sens à
cette échelle. Le seul signal de fraîcheur exploitable est la date de modification du profil côté
Jarvi.

**Or Jarvi l'expose et nous la chargeons déjà** : `JarviProfile::$updatedAt` est lu à chaque
ingestion, sert à faire avancer le curseur incrémental — puis est **jeté**. Aucune colonne ne le
reçoit. C'est de la donnée déjà payée, non conservée.

## Objectif

Conserver la date de dernière modification du profil chez Jarvi, pour dater la donnée, l'afficher et
la filtrer.

## Ce qui a été livré, et ce que la mesure a révélé

`candidate_jarvi.jarvi_updated_at` est peuplée à l'ingestion : **1 694 profils sur 1 699 (99,7 %)**
après une réingestion `--full`.

**Mais la date ne mesure PAS la fraîcheur du candidat.** Distribution constatée :

| Date | Profils |
| --- | ---: |
| 2026-07-07 | **1 607** |
| 2026-07-09 | 23 |
| autres jours de juillet | 14 |
| mai–juin 2026 | 50 |

**95 % du vivier partage la même date à la journée près** : c'est l'empreinte d'une opération de
masse côté Jarvi (import ou resynchronisation), pas d'une activité de candidat. `updatedAt` date le
dernier **écrit** dans Jarvi, quelle qu'en soit la cause.

Conséquence assumée : la colonne reste (c'est le bon champ, elle ne coûte rien et se chargeait déjà),
mais **aucun filtre « profil récent » ne doit être construit dessus aujourd'hui** — il trierait le
vivier selon la date d'un batch.

### Complément du 2026-07-24 : ce n'est pas `updatedAt` le problème, c'est le vivier

Une sonde de l'API Jarvi (200 profils) a montré que **quatre champs de date mentent de la même
façon** — tous portent la marque du même import de masse :

| Champ Jarvi | Rempli | Même jour |
| --- | ---: | ---: |
| `updatedAt` | 100 % | 83 % |
| `addedAt` | 100 % | 83 % |
| `createdAt` | 100 % | 82 % |
| **`lastResumeUpdatedAt`** | **100 %** | **83 %** |

`lastResumeUpdatedAt` est le piège : rempli à 100 %, son nom promet exactement la fraîcheur
recherchée. Ingéré sans mesurer sa distribution, il aurait affiché « CV mis à jour le 7 juillet » sur
83 % du vivier.

**Le seul champ honnête est `lastInteractionDate`** (6 %, étalé de février à juillet), avec son canal
`lastInteractionType`. Il concorde avec les 60 profils à activité de `historyEntries`. Son
exploitation fait l'objet de **`121`** — et sa valeur n'est pas la fraîcheur mais **d'éviter un
double contact**.

Jarvi expose aussi `files[].createdAt` (date de dépôt du CV), que notre requête GraphQL ne demande
pas. Sans intérêt : 2 % des profils ont un CV.

## Périmètre

- Colonne `jarvi_updated_at` sur `candidate_jarvi`, alimentée à l'ingestion depuis
  `JarviProfile::$updatedAt` (déjà disponible, aucun appel supplémentaire).
- Réingestion `--full` pour peupler le stock existant.
- Exposition côté recherche/DTO : **suspendue**, cf. la mesure ci-dessus.

## Invariants

- Date **de la source**, jamais réécrite par nos propres timestamps ; `synced_at` reste distinct et
  garde son sens (quand nous avons lu).
- Absente = absente : un profil sans `updatedAt` ne se voit pas attribuer une date par défaut.

## Critères d'acceptation

- [x] `jarvi_updated_at` peuplé pour tout profil dont Jarvi fournit la date (99,7 %).
- [x] Taux de remplissage mesuré et inscrit dans la roadmap.
- [x] Test d'ingestion : la date de la source est conservée et reste distincte de `syncedAt`.
- [x] ~~Filtre « modifié depuis »~~ — **définitivement écarté**. La sonde du 2026-07-24 a montré que
      les quatre champs de date candidats portent la même marque de batch. Jarvi expose bien une date
      d'activité (`lastInteractionDate`) : elle est exploitée par `121`, et c'est la bonne réponse au
      besoin d'origine. Cette carte n'a plus d'objet propre.

## Points de vigilance

- **Ne pas confondre les trois dates** : `updatedAt` Jarvi (la donnée bouge), `synced_at` (nous
  lisons), `parsedAt` du CV (un document a été analysé). Les nommer distinctement à l'affichage.
- **Ce que `updatedAt` mesure vraiment** : vérifié le 2026-07-22, et le soupçon était fondé — la
  date reflète l'écriture côté Jarvi, dominée à 95 % par une opération de masse. Ne jamais la
  présenter comme « profil mis à jour par le candidat ».
- **Le CV reste marginal** (3,5 %) : ne pas bâtir de filtre « CV à jour » sur cette base.
