# TASK 120 — admin : référentiels qualifiés — consultation & validation

- **Créée le** : 2026-07-23.
- **Statut** : ✅ fait le 2026-07-25 — API + écran /referentiels, validation tracée survivant à la réingestion.
- **Type** : Full-stack — API (CRUD statut) + Front (webapp admin, `/referentiels`).
- **Lié à** : `113` (promotion `candidat → validé` — **cette carte en est l'implémentation UI**),
  `103`/`105`/`107`/`111` (producteurs des référentiels), `062` (filtres conditionnés au statut).
- **Réf** :
  - `dev-book/tasks/113-vocabulaire-promotion-candidat-valide.md` (le quoi ; ici le comment UI)
- **Placement** : route admin dédiée, à côté de `/vivier` (`119`) qui en est la lecture seule.
  ⚠️ **Collision de nom à trancher** : `dev-book/cadrage/admin/ui/arborescence.md` liste déjà un
  `/referentiels` en V2, mais il désigne **autre chose** — le vocabulaire des *statuts de suivi
  candidat* côté entreprise. Deux référentiels sans rapport ne peuvent pas partager une route :
  soit celui-ci prend `/referentiels` et l'autre est renommé, soit l'inverse.

## Livré le 2026-07-25

`GET /v1/admin/referentials/{type}` (page triée par fréquence, libellés inclus, couverture cumulée,
répartition des statuts) et `POST /v1/admin/referentials/reviews` (bascule VALIDE/DEPRECIE tracée
« qui, quand »). Écran `/referentiels` : sélection, action de masse prudente, filtre de statut,
pagination. 5 tests fonctionnels.

**Le piège central est fermé** : une validation faite à l'écran **survit à la réingestion**.
`VocabularyImporter` ne rétrograde jamais sur un `candidat` de fichier — qui signifie « pas encore
relu », pas « rejeté ». Vérifié bout-à-bout (déprécié via l'API, réimport, statut et traçabilité
conservés). Les deux dettes préalables (`113` : code stable, purge transactionnelle) sont soldées.

## Contexte

**6 598 objets sont en statut `candidat`, aucun validé** (mesuré le 2026-07-22) :

| Table | Objets | Validés |
| --- | ---: | ---: |
| `vocabulary_concept` | 493 | 0 |
| `vocabulary_label` | 1 072 | 0 |
| `vocabulary_concept_similarity` | 3 826 | 0 |
| `company_sector` | 1 207 | 0 |

Tant que rien n'est validé, **aucun filtre dur n'est possible** : `candidat` sert à élargir une
recherche, jamais à affirmer qu'un profil « est » de tel secteur. C'est le plafond actuel de la
recherche (cf. `113`, `062`), et il ne se lève que par une relecture humaine.

Afficher ces tables en lecture seule ne servirait à rien. **Ce qui manque est le geste de
validation**, et c'est le seul écran admin qui débloque un vrai gain fonctionnel.

## Objectif

Un écran admin qui liste les référentiels qualifiés et permet de **valider / déprécier** chaque
objet, avec traçabilité, en priorisant l'effort là où il compte.

## À livrer

### Consultation

Les quatre référentiels, chacun triable et filtrable par statut (`candidat` / `validé` / `déprécié`) :

- **Concepts métier / compétence** avec leurs libellés et leur fréquence.
- **Entreprises classées** avec leur secteur et leur fréquence.
- **Concepts proches** — en lecture, rattachés à leur concept, **pas un tableau brut de 3 826
  lignes** : consultés depuis un concept, comme « voisins de X ».

### Validation

- Bascule `candidat → validé` / `→ déprécié`, **traçée** (qui, quand) — l'exigence de `113`.
- **Priorisation par fréquence** : valider les 50 concepts les plus portés couvre l'essentiel des
  recherches sans relire les 493. L'écran doit trier par fréquence décroissante par défaut et
  afficher la couverture cumulée (« ces N concepts validés couvrent X % des occurrences »).
- Action de masse prudente (valider une page filtrée), jamais un « tout valider » aveugle.

### Effet

- La recherche distingue `validé` (filtre dur autorisé) de `candidat` (élargissement seul), cf. `062`.

## Signaux à faire remonter pour guider le relecteur

- **`Stage / Internship`, concept de poste n°1 (411 profils), est un statut** : à déprécier ou
  requalifier avant tout usage des postes comme filtre — c'est le premier objet que le relecteur doit
  voir.
- **`AUTRE` = 11 % des expériences par secteur** : entreprises non classées, candidates à un second
  passage de classification (`105`), pas à une validation.
- **Les libellés issus de reconnaissance partielle** (`118`) méritent un œil : ~7 % sont faux.

## Invariants

- **Tout ce que la machine produit reste `candidat`** jusqu'à geste humain — invariant du projet.
- Le statut change la **confiance**, pas la disponibilité : un objet `candidat` reste utilisable en
  élargissement, il ne disparaît pas.
- Réservé aux rôles `ROLE_ADMIN_*` : la validation engage la plateforme entière (référentiel commun).

## Critères d'acceptation

- [ ] Endpoints admin : liste paginée/filtrée par statut, et bascule de statut avec traçabilité.
- [ ] Écran : 4 référentiels, tri par fréquence, couverture cumulée affichée.
- [ ] La bascule enregistre qui et quand.
- [ ] La recherche applique la distinction `validé` / `candidat` (au moins un filtre le prouve).
- [ ] `openapi.json` régénéré ; `api-quality` vert.

## Points de vigilance

- **Ne pas faire de la relecture exhaustive un préalable** : prioriser par fréquence, livrer la
  valeur dès les premiers concepts validés.
- **Dépréciation ≠ suppression** : un concept déprécié reste en base (un profil peut le porter) ;
  il cesse seulement d'être proposé.
- **Concepts proches** : la validation porte sur les **concepts**, pas sur les 3 826 relations une à
  une — les relations d'un concept validé héritent de la confiance, sauf déclassement explicite.
