# TASK 086 — API : la carte candidat qui peuple la Table et l'inspecteur

- **Créée le** : 2026-07-13.
- **Statut** : 🟡 **partiellement livrée** (2026-07-13) — §1 (années d'expérience), §2 (employeurs
  masqués) et §4 (DTO resserré) **faits et testés** ; **§3 (`isNew` par carte) reste à faire** : il
  demande le `lastViewedAt` de la tâche `072`, qui n'est pas commencée.
- **Type** : contrat d'exposition / masquage.
- **Dépend de** : tâche `061` (années d'expérience calculées à l'ingestion), `072` (recherche
  persistée, `lastViewedAt`), `073` (favoris & shortlist par recherche), `074` (joignabilité,
  nouveaux champs, retrait du salaire).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` § Masquage champ par champ
  - `dev-book/cadrage/entreprise/ui/vue_la_table.md`
  - `dev-book/cadrage/entreprise/ui/inspecteur.md`
  - Mockup : `dev-book/cadrage/wireframes/draft/mockup-espace-entreprise-vue-cockpit-latest.html`

## Contexte

`CandidateCardOutput` — la carte servie par `GET /v1/search/candidates` — **ne suffit pas à peindre
une ligne de la Table**, ni la ligne d'identité de l'inspecteur. Le relevé champ par champ du mockup
cockpit fait apparaître deux manques et une fuite :

- **Les années d'expérience n'existent dans aucun DTO.** C'est pourtant une colonne de la Table
  (« Exp. ») et la moitié de la ligne d'identité de l'inspecteur. Jarvi ne les donne pas : la requête
  GraphQL ne récupère que `positions { title companyName startDate endDate }`. Elles se **dérivent**
  des périodes — c'est ce que la tâche `061` fait déjà pour **filtrer** (`experienceYearsMin`). Reste
  à les **exposer**.
- **L'employeur fuite sur un profil masqué.** `CandidateMapper` sérialise `workExperiences` **en clair
  quel que soit l'état de dévoilement**. Or « Développeur Symfony senior · Atlas Group · Lyon · 11 ans »
  suffit à retrouver la personne sur LinkedIn : c'est de la **ré-identification**, et le tableau de
  masquage ne tranche pas ce champ.
- **Le tab « Nouveaux » de la Table n'a pas de donnée.** La tâche `072` expose `newCount` **par
  recherche** (le badge `+N` de la liste des recherches) ; segmenter les **lignes** demande un flag
  **par carte**.

## Objectif

Faire de la carte candidat le **contrat unique** de la Table et de l'inspecteur : tout ce que ces deux
surfaces affichent y est, rien de ce qu'elles n'affichent pas n'y traîne, et rien de ré-identifiant
n'en sort avant dévoilement.

## Périmètre

### 1. Exposer les années d'expérience

- `experienceYears` (entier) sur la **carte** et sur la **fiche** — lu depuis la colonne normalisée
  calculée à l'ingestion par la tâche `061`.
- **Visible sur un profil masqué** : c'est un **agrégat**, il ne ré-identifie personne.

### 2. Masquer les employeurs jusqu'au dévoilement

- `workExperiences[].companyName` → **`null` tant que le profil n'est pas dévoilé** pour
  l'organisation. Les **intitulés de poste** (`title`) et les **périodes** restent visibles : ils
  qualifient sans nommer.
- Le **contrat de masquage** affiché au recruteur gagne une ligne — `Employeurs (actuel & passés)` —
  au même titre que les coordonnées et le CV : c'est **ce que le crédit achète**.
- L'employeur courant (entreprise + ancienneté) n'apparaît dans l'inspecteur **qu'après dévoilement**.
  Il vient de `GET /v1/candidates/{id}` : **inutile de le porter sur la carte de recherche**.

### 3. Segmenter les « nouveaux » ligne par ligne

- Chaque carte porte `isNew` : le profil a **rejoint le jeu de résultats** de la recherche courante
  depuis le `lastViewedAt` du **recruteur courant** (tâche `072`).
- Même sémantique que `newCount`, à la maille de la ligne — **à ne pas confondre avec une « fraîcheur
  de profil »**, notion retirée du produit.
- Un profil **entré parce qu'un champ a changé** (fenêtre de présence, disponibilité) est **nouveau
  dans la recherche** : c'est bien un signal, pas un bruit.

### 4. Resserrer le DTO de carte

- **Salaire** : `salaryExpected` / `salaryMin` **disparaissent des réponses** et **ne servent à aucun
  filtre** (tâche `074`). La donnée reste **conservée telle quelle dans `candidate_jarvi`** (réplica
  JSON opaque) — on ne la perd pas, on ne l'expose pas.
- Vérifier que ce qui reste sur la carte est **affiché ou filtré** — un champ que ni la Table, ni
  l'inspecteur, ni un critère ne réclament n'a rien à y faire.

## Invariants

- **Masquage serveur** : le front ne reçoit **jamais** une donnée masquée, employeur compris. Après
  dévoilement, il **re-fetch**.
- Un **agrégat** (années d'expérience) n'est pas une donnée identifiante — un **nom propre**
  (employeur) l'est. C'est la ligne de partage.
- **Une seule requête peint une page de Table** : pas de `GET /candidates/{id}` par ligne.

## Critères d'acceptation

- [x] `experienceYears` est exposé sur la carte **et** sur la fiche, y compris masqué, et alimente la
      colonne « Exp. » sans appel supplémentaire.
- [x] Un profil **non dévoilé** n'expose **aucun nom d'employeur**, ni sur la carte, ni sur la fiche.
- [x] Un profil **dévoilé** expose ses employeurs (actuel et passés).
- [x] Le contrat de masquage rendu à l'UI liste **`Employeurs (actuel & passés)`**.
- [ ] `isNew` est calculé par rapport au `lastViewedAt` du **recruteur courant** et alimente le tab
      « Nouveaux » ; deux recruteurs sur une **même recherche partagée** obtiennent des `isNew`
      **indépendants**. → **bloqué par la tâche `072`** (pas de `SavedSearch` persistée).
- [x] Aucun salaire ne sort de l'API ; la donnée reste présente en base.
- [x] Tests : un profil masqué ne fuite ni employeur, ni coordonnée, ni photo, ni prénom complet.
- [x] `make api-quality` vert (148 tests, 919 assertions ; PHPStan, CS-Fixer, Deptrac).

## Hors périmètre

- Calcul des années d'expérience à l'ingestion → tâche `061` (ici, on **expose** le résultat).
- `hasEmail` / `hasPhone`, « ouvert aux opportunités », villes cibles, retrait du salaire de la
  sérialisation → tâche `074`.
- Favoris / shortlist rendus ligne par ligne → tâche `073`.
- Référentiel de postes et agrégats de facettes → tâche `075`.
