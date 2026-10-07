# TASK 072 — API : entité « recherche persistée » (critères, auteur, partage lecture seule)

- **Créée le** : 2026-07-12.
- **Statut** : ✅ faite (2026-07-14).
- **Type** : nouvelle capacité / socle du cockpit entreprise.
- **Dépend de** : tâche `014` (recherche réplica), `004` (organisation).
- **Bloque** : tâches `073` (favoris/shortlist), `076` (imputation crédits), `077` (export).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/les_2_outils.md` §4
  - `dev-book/cadrage/entreprise/ui/console_contextuelle.md` §3–§5
  - `dev-book/cadrage/entreprise/ui/arborescence.md` § Portées & accès

## Contexte

La refonte cockpit (2026-07-12) fait de la **recherche** l'objet central de l'espace entreprise : elle
porte ses critères, ses favoris, sa shortlist et ses crédits consommés. Aucune entité ne la représente
aujourd'hui — le front du mockup la garde en mémoire.

Sans elle, rien du cockpit n'est persistable : ni les favoris, ni la shortlist, ni l'export, ni
l'imputation des crédits.

## Objectif

Une entité **recherche**, appartenant à **un recruteur**, persistée au fil de l'eau, et
**partageable en lecture seule** avec les autres membres de l'`Organization`.

## Périmètre

- Entité `SavedSearch` (nom technique à confirmer) :
  - `owner` (recruteur auteur), `organization` ;
  - `criteria` : liste **ordonnée** de critères `{ facet, value, group }` — **l'ordre de sélection est
    signifiant** (il produit le nom auto côté front) ;
  - `name` (dérivé des critères par défaut, renommable) + `isAutoNamed` (renommer manuellement le
    désactive ; vider le nom le réactive) ;
  - `isShared` (booléen) — privée par défaut.
- **Badge de nouveautés** (`+N`) : `lastViewedAt` par **(recherche, recruteur)** — un membre qui
  consulte une recherche **partagée** a son propre repère de lecture.
  - `GET /v1/searches` expose `newCount` : les profils **ayant REJOINT le jeu de résultats** depuis le
    `lastViewedAt` du recruteur courant ;
  - ouvrir la recherche **remet le repère à jour** (le badge retombe à 0).

> ⚠️ **« A rejoint le jeu de résultats » ≠ « est entré dans le vivier ».** Un profil rejoint le jeu
> parce qu'il est nouveau **ou** parce qu'un champ a changé et le rend matchant : **fenêtre de présence
> au Maroc** ⭐, disponibilité, vérifié en entretien, ouvert aux opportunités, consentement.
>
> **C'est le cas d'usage central de showcase-recruiter-webapp-saas** : un candidat présent depuis six mois qui déclare un
> séjour au Maroc du 8 au 24 juillet entre dans la recherche « présent < 30 j » **sans être un nouveau
> profil**. Compter les seules entrées du vivier le raterait.
>
> **Implémentation** : ce delta ne se déduit pas d'un `createdAt`. Il faut **matérialiser
> l'appartenance** d'un profil au jeu de résultats d'une recherche (table de matching recalculée à
> l'ingestion), ou horodater les **changements de champs matchants**. **Approche à trancher** — c'est
> le point technique le plus lourd de cette tâche.
>
> **Elle conditionne aussi les alertes** (🔭 V2) : l'alerte **pousse** exactement l'événement que le
> badge **compte**. Bien poser cette mécanique ici, c'est livrer les alertes presque gratuitement plus
> tard ; la bâcler, c'est les réécrire. Les alertes y ajouteront un **second événement** — la *mise à
> jour* d'un profil déjà dans le jeu (fenêtre Maroc, disponibilité, vérifié, ville actuelle) — qui
> suppose de **détecter les changements de champs**, pas seulement l'appartenance. En tenir compte dans
> le choix d'architecture. Cf. `ui/console_contextuelle.md` §3.0bis.
- Routes CRUD scopées recruteur :
  - `GET /v1/searches` — les siennes **+ celles partagées** de son organisation (auteur exposé) ;
  - `POST`, `PATCH`, `DELETE /v1/searches/{id}` — **auteur uniquement** ;
  - `POST /v1/searches/{id}/duplicate` — accessible à **tout membre pouvant la lire** ; la copie
    appartient à celui qui duplique, `isShared = false`.
- **Autorisations — deux règles distinctes** (voter) :
  - **lecture** : auteur, **ou** membre de la même organisation **si `isShared`** ;
  - **écriture** (critères, nom, suppression, favoris, shortlist) : **auteur uniquement**. Un
    destinataire est **toujours en lecture seule** — pas de co-édition.
- Auto-enregistrement : une recherche existe dès le premier critère posé ; pas d'action « Enregistrer ».

## Invariants

- **Un seul auteur par recherche.** Aucune écriture concurrente à gérer, aucun verrou.
- Une recherche **non partagée est invisible** des autres membres, **même de la même organisation** —
  contrairement aux dévoilés et au solde de crédits, qui sont communs.
- Le **nom auto** est calculé côté front (ordre des critères) ; l'API stocke le résultat, elle n'en
  porte pas la règle.

## Critères d'acceptation

- [ ] Un recruteur crée, renomme, duplique et supprime ses recherches.
- [ ] `GET /v1/searches` retourne les siennes + les partagées de l'organisation, avec l'auteur.
- [ ] Un membre **ne peut pas lire** une recherche non partagée d'un collègue (403).
- [ ] Un membre **ne peut pas modifier** une recherche partagée dont il n'est pas l'auteur (403).
- [ ] Il **peut la dupliquer** ; la copie lui appartient et n'est pas partagée.
- [ ] L'ordre des critères est préservé au aller-retour.
- [ ] `newCount` reflète les profils entrés depuis la **dernière consultation du recruteur courant**,
      et **retombe à 0** quand il ouvre la recherche.
- [ ] Deux recruteurs sur une **même recherche partagée** ont des `newCount` **indépendants**.
- [ ] `make api-quality` vert.

## Décisions de mise en œuvre

- **`SavedSearch` existait déjà** (livrée avec la 030) : la tâche a fait évoluer l'entité, pas créée.
  Les routes passent de `/organizations/{id}/saved-searches` à **`/v1/searches`** — rupture de
  contrat assumée, l'ancienne forme n'était consommée par aucune page.
- **« A rejoint le jeu de résultats » → table de matching matérialisée** (`SearchResultMembership` :
  `search`, `candidate`, `firstMatchedAt`), recalculée à l'ingestion Jarvi et à chaque écriture de
  critères. Approche retenue contre les deux autres pistes (horodater les champs matchants ; se
  rabattre sur `createdAt`) : elle seule capte le cas d'usage central — un profil ancien qui déclare
  une fenêtre de présence au Maroc **rejoint** la recherche sans être nouveau. Les alertes V2
  pousseront l'événement que ce recalcul date déjà.
- Le recalcul est aussi déclenché **à l'écriture** des critères : sans lui, tout le vivier matchant
  compterait comme « nouveau » au premier passage d'ingestion et le badge afficherait un +N absurde.
- **Limite connue** : Postgres stocke ces horodatages à la seconde (`TIMESTAMP(0)`). Une ingestion et
  une ouverture de recherche survenant dans la même seconde peuvent masquer une entrée du badge.
  Sans effet en pratique (l'ingestion est un batch quotidien) ; à traiter en passant les colonnes en
  `TIMESTAMP(6)` si le besoin devient réel.

## Hors périmètre

- **Alertes** sur recherche (🔭 V2, y compris « activer une alerte pour un collaborateur »).
- **Espace collaboratif** : équipes/sous-groupes, commentaires sur une recherche, notification
  nominative, résumés périodiques → 🔭 V2, cf. `vue_d_ensemble.md` §4bis.
- Page de gestion des recherches (archivage).
- Modèles de recherche pré-établis : **figés produit**, portés par le front (pas une entité).
