# TASK 124 — Admin : déprécier un libellé (mot à mot) dans l'écran référentiels

- **Créée le** : 2026-07-24.
- **Statut** : 🚧 en cours.
- **Type** : API + webapp admin — extension de l'écran de validation des référentiels.
- **Lié à** : `120` (validation des concepts — même patron, un cran plus haut), `123` (volet A :
  l'expansion consommera le statut des libellés), `113` (promotion candidat→validé).
- **Réf** : `dev-book/resources/taxonomie/README.md`, `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`.

## Le besoin

Un concept peut être bon tout en portant un synonyme parasite. Ex. « Développeur logiciel » traîne
`stagiaire en développement web` : le concept est juste, mais ce libellé-là ne doit pas élargir la
recherche (un stagiaire n'est pas un développeur confirmé). Le `120` valide/déprécie au niveau
**concept** ; il manque le cran en dessous, **au niveau libellé**.

## Décision

**Déprécier = ignorer temporairement, réversible.** Le libellé passe en `DEPRECIE`, reste **affiché
barré** (on garde la mémoire de la décision), et peut être **restauré** (`CANDIDAT`). Pas de
suppression. C'est le même enum 3-états que les concepts, appliqué au libellé — pas de nouvel état.

Choix écarté : **réaffecter** le libellé à un autre concept (« Stage »). Plus propre sémantiquement
mais c'est une vraie édition de taxonomie (opération « déplacer »), hors de ce lot. « Exclure » suffit
au besoin.

## Le terrain est déjà prêt

- `vocabulary_label.status` **existe déjà** (`CANDIDAT/VALIDE/DEPRECIE`).
- La réingestion **préserve le statut** : `VocabularyImporter::importLabel` fait un upsert par
  `(concept, label, lang)` et ne met à jour que la fréquence — une décision humaine survit tout seul
  (pas de purge, contrairement aux similarités corrigées en `1a`).

## Périmètre

- [ ] **Entité** : `reviewedBy` / `reviewedAt` sur `VocabularyLabel` (traçabilité « qui / quand »,
      comme le concept en `120`). `status` déjà présent.
- [ ] **Provider** : `AdminReferentialProvider` renvoie les libellés **structurés**
      `{uuid, label, lang, status, frequency}` au lieu du `string_agg` texte — pour les rendre
      adressables et afficher leur statut.
- [ ] **API** : `POST /admin/referentials/labels/reviews` — cible des libellés par **uuid** + statut
      voulu (`DEPRECIE` pour exclure, `CANDIDAT` pour restaurer ; `VALIDE` refusé en 422 — un libellé
      ne se « valide » pas seul, c'est le concept qui l'est). Trace `reviewedBy`/`reviewedAt`. Renvoie
      les uuids inconnus. Gardé `ROLE_ADMIN`.
- [ ] **UI** : sur `/referentiels`, les synonymes deviennent des **puces cliquables** ; clic =
      déprécier (barré/grisé), re-clic = restaurer. Statut du concept inchangé (indépendant).
- [ ] **Tests** fonctionnels : déprécier trace + survit à la réingestion ; restaurer ; uuid inconnu ;
      `VALIDE` refusé ; garde admin.
- [ ] Contrat OpenAPI + types front régénérés.

## Invariants

- La décision humaine sur un libellé **survit à la réingestion** (upsert par `(concept, label, lang)`,
  statut et trace jamais réécrasés).
- Lecture/écriture gardées `ROLE_ADMIN`.
- Le statut du **concept** et celui de ses **libellés** sont indépendants (deux niveaux de décision :
  concept = affirmer/élargir, libellé = exclure un synonyme précis).

## Hors périmètre

- **L'expansion** qui consomme `label.status` (= volet A de `123`) — cette carte prépare la donnée,
  pas le moteur.
- Réaffectation d'un libellé vers un autre concept (édition de taxonomie).
- Action de masse sur les libellés (V2 si besoin ; on cible d'abord le retrait ponctuel de bruit).

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md` — le statut au niveau libellé.
