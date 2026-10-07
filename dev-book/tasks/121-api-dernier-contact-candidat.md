# TASK 121 — API : dernier contact avec le candidat (date + canal)

- **Créée le** : 2026-07-24.
- **Statut** : ✅ fait le 2026-07-24 — date et canal ingérés, exposés même sur un profil masqué.
- **Type** : API — ingestion Jarvi / qualification à l'import.
- **Lié à** : `116` (fraîcheur — cette carte en est la **conclusion utile**), `092` (provenance),
  `061`/`062` (recherche et filtres), `119` (restitution admin).
- **Réf** : sonde REST `v2/profiles` du 2026-07-24 (200 profils, `orderBy updatedAt desc`).

## Contexte

La carte `116` cherchait un signal de fraîcheur et concluait que `updatedAt` ment (95 % du vivier
porte la date d'un même import de masse). Une sonde de l'API Jarvi a montré que **le problème est
plus large que `updatedAt`, et que la solution est ailleurs**.

Jarvi expose à la racine du profil plusieurs champs de date que **nous n'ingérons pas** :

| Champ Jarvi | Rempli | Distribution | Verdict |
| --- | ---: | --- | --- |
| `updatedAt` | 100 % | 83 % le 07-07 | ❌ batch |
| `addedAt` | 100 % | 83 % le même jour | ❌ batch |
| `createdAt` | 100 % | 82 % le même jour | ❌ batch |
| **`lastResumeUpdatedAt`** | **100 %** | **83 % le 07-07** | ❌ **batch — le piège** |
| **`lastInteractionDate`** | **6 %** | **02-05 → 07-24, étalé** | ✅ **honnête** |
| `lastInteractionType` | 6 % | LinkedIn message, WhatsApp, RDV… | ✅ |
| `firstInteractionDate` | 6 % | 2016 → 2026 | ✅ |
| `isOpenToNewOpportunities` | **0 %** | — | jamais renseigné par Jarvi |
| `countApplicationsFromThisProfile` | 2 % | — | marginal |

**`lastResumeUpdatedAt` est le piège à ne pas rater** : rempli à 100 %, et son nom promet exactement
ce qu'on cherchait. Ingéré sans mesurer sa distribution, il aurait affiché « CV mis à jour le
7 juillet » sur 83 % du vivier — un mensonge parfaitement crédible.

Sur le CV lui-même, `files[]` expose bien `createdAt` (date de dépôt) et `updatedAt`, que notre
requête GraphQL ne demande pas (elle ne prend que `fileId parsedAt shouldBeParsed`). Sans intérêt
ici : **2 % des profils ont un CV**.

## Objectif

Ingérer le **seul signal honnête** — la dernière interaction — et l'exposer pour ce qu'il est : un
historique de contact, **jamais** une fraîcheur de profil.

## Ce qui a été livré

- `candidate_jarvi.last_interaction_at` + `last_interaction_type`, alimentées à l'ingestion depuis
  `lastInteractionDate` / `lastInteractionType` ajoutés aux 3 requêtes GraphQL. **Aucun appel
  supplémentaire** : les champs voyagent dans la réponse qu'on lisait déjà.
- **Exposés MÊME masqué**, sur la carte et le détail. C'est délibéré et conforme à la ligne de
  partage du masquage : ils disent qu'un échange a eu lieu, jamais l'adresse utilisée — même logique
  que `hasEmail` / `hasPhone`. Et c'est **avant** de dépenser un dévoilement qu'un recruteur a
  besoin de savoir qu'un confrère est déjà passé.

Mesuré sur 200 profils réingérés : **45 profils datés**, du 30 janvier au 2 juillet — étalé, donc
honnête. Canaux constatés :

| Canal | Profils |
| --- | ---: |
| JOB_APPLICATION | 11 |
| LINKEDIN_MESSAGE_RECEIVED | 10 |
| WHATSAPP_RECEIVED / SENT | 7 |
| EMAIL_RECEIVED / SENT | 6 |
| autres (appel, RDV, InMail…) | 8 |

Les 11 candidatures sont le signal le plus fort : ces gens ont postulé, ils ne sont pas seulement
sourcés.

## Périmètre

- Deux colonnes sur `candidate_jarvi` : `last_interaction_at` (nullable) et `last_interaction_type`.
- Ajout des champs à la requête GraphQL d'ingestion — **la donnée arrive déjà dans la réponse**,
  aucun appel supplémentaire, aucun coût.
- Exposition : « dernier contact : 24 juillet, WhatsApp ». Vide quand il n'y a pas eu de contact.
- Recoupement possible avec `historyEntries[].occurredAt` déjà présent dans `rawPayload` (60 profils
  à activité) — les deux sources doivent concorder.

## Valeur

Le bénéfice n'est pas la fraîcheur, c'est **d'éviter un double contact** : un recruteur voit qu'un
candidat a déjà été approché le 24 juillet par WhatsApp, et par qui. Utile même à 6 % de couverture,
car quand l'information existe, elle est décisive.

## Hors périmètre

- **Ne pas ingérer** `lastResumeUpdatedAt`, `addedAt` ni `createdAt` : trois champs de plus portant
  la même marque de batch. Aucun n'apporte de signal, tous invitent à un affichage trompeur.
- Aucun **tri ni filtre « profil récent »** : 94 % du vivier serait vide, et le classement refléterait
  qui a été contacté, pas qui est disponible.

## Invariants

- Champ **dérivé de la source**, jamais réécrit par nos propres timestamps ; distinct de `syncedAt`
  (notre lecture) et de `jarviUpdatedAt` (l'écrit côté Jarvi).
- **Absent = absent** : pas de contact ne devient pas une date par défaut.

## Critères d'acceptation

- [x] `last_interaction_at` et `last_interaction_type` peuplés à l'ingestion, taux inscrit dans la
      roadmap.
- [x] Exposé comme « dernier contact », avec son canal — jamais étiqueté « fraîcheur » ou « récent ».
- [x] Test d'ingestion : date et canal conservés, **absence non inventée**, et visibilité sur un
      profil masqué vérifiée.
- [ ] Concordance fine avec `historyEntries[].occurredAt` : les deux sources concordent en ordre de
      grandeur (45 vs 60 profils), l'écart n'a pas été élucidé au cas par cas.

## Points de vigilance

- **Échantillon de la sonde** : 200 profils triés `updatedAt desc`, donc **non aléatoire** — les taux
  ci-dessus sont des ordres de grandeur à re-mesurer sur le vivier complet à l'ingestion. La
  conclusion (batch vs étalé) est en revanche robuste : 83 % sur un même jour ne s'explique pas par
  l'échantillonnage.
- **Le canal est une donnée de contact** : vérifier s'il relève du masquage (`recherche_et_masquage`)
  avant de l'exposer à une entreprise qui n'a pas dévoilé le profil.
- **Ne pas conclure « candidat inactif »** d'une absence de contact : elle dit que *nous* ne l'avons
  jamais approché, pas qu'il est indisponible.
