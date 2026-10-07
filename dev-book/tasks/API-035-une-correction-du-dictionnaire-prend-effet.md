# TASK API-035 — une correction du dictionnaire prend effet

- **Créée le** : 2026-08-10.
- **Session** : `9d0eb3f2-2064-4ffd-94a9-dbc606ce1450`.
- **Statut** : ✅ fait le 2026-08-10 — l'import retire et rend les libellés que le fichier ne
  revendique plus, et `app:vocabulary:reproject` rejoue le dictionnaire sur le vivier déjà chargé
  sans repasser par Jarvi.
- **Type** : feat — outillage du dictionnaire. Aucune migration, aucune colonne nouvelle, aucun
  contrat d'API touché.
- **Tests** : fonctionnels sur `app:vocabulary:import` (un libellé retiré du fichier disparaît des
  trois consommateurs, puis revient à l'identique si le fichier le rend) et sur
  `app:vocabulary:reproject` (un profil change de métier après un changement de dictionnaire, sans
  appel à Jarvi).
- **Réf** : aval `API-036` (qui s'en sert pour corriger les architectes) · amont task `095` (métiers
  exercés), `113` (le fichier fait autorité sur le statut), `118` (reconnaissance partielle des
  intitulés) · `api/src/Domain/Vocabulary/VocabularyImporter.php` ·
  `api/src/Domain/Jarvi/ProfileIngestor.php` ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md`.

## Contexte

`api/data/vocabulary/taxonomy.json` est censé faire autorité sur le dictionnaire : il est versionné,
il se relit en `git diff`, et la task `113` en a fait la source du statut de relecture humaine. Deux
choses l'empêchent aujourd'hui de tenir ce rôle.

**L'import sait attacher, il ne sait pas détacher.** `VocabularyImporter::importLabel()` cherche la
ligne `(concept, label, langue)`, la met à jour si elle existe, la crée sinon. Rien ne regarde ce que
la base porte **en trop**. Conséquence : un rattachement erroné est définitif. Retirer un terme du
fichier ne produit aucun effet ; le déplacer d'un concept vers un autre le laisse sur les deux, et le
résolveur d'intitulés se retrouve avec un terme qui désigne deux sens — il en garde un, arbitrairement.

**Rien ne rejoue le dictionnaire sur le vivier déjà chargé.** Le rattachement d'un profil à un métier
est calculé à l'**ingestion** (`ProfileIngestor::syncJobExperience()`, via `TitleConceptResolver`),
puis figé dans `candidate_job_experience`. Corriger la taxonomie et la charger ne déplace donc aucun
profil : le concept créé reste sans personne. Or l'auto-complétion ne propose que les concepts
**qu'au moins un profil exerce** (`SuggestionFinder::jobs()`, jointure sur
`candidate_job_experience`). Une correction du dictionnaire est ainsi invisible jusqu'à la prochaine
ingestion complète — coûteuse, dépendante de la clé Jarvi, et sans rapport avec ce qu'on vient de
changer. En développement, elle n'arrive tout simplement jamais.

Les deux manques se répondent : le premier interdit de corriger, le second interdit de constater.

## Périmètre

- **`VocabularyImporter`** : pour chaque concept **décrit par le fichier**, les libellés que le
  fichier ne lui donne plus sont retirés en suppression douce (`deleted_at`). Un libellé retiré puis
  rendu au fichier est **restauré**, pas recréé.
- **Le filtre soft delete est levé le temps de l'import**, sinon la relecture ne voit pas ce qu'elle
  a retiré et l'import tente de recréer une ligne que l'unicité `(concept, label, langue)` refuse.
- **`AbstractEntity::restore()`** : réciproque de `softDelete()`, absente jusqu'ici.
- **`VocabularyImportReport`** gagne `labelsRemoved` et `labelsRestored` ; la commande les annonce.
- **`app:vocabulary:reproject`** : nouvelle commande qui recalcule, depuis la **réplica locale**,
  les métiers exercés et le concept du poste actuel. Options `--limit` et `--dry-run`. La règle vit
  dans un objet de domaine ; la commande la pilote et rend compte.
- **La règle de pose des métiers exercés est extraite** de `ProfileIngestor` dans un objet dédié,
  appelé par l'ingestion et par la reprojection. Une seule écriture de la règle, deux moments.
- **`make dev-vocabulary-refresh`** enchaîne désormais `enrich` → `import` → `reproject`.

## Hors périmètre

- **Toute correction de contenu du dictionnaire** — aucun concept n'est créé, renommé ni déplacé
  ici. C'est `API-036` qui exerce l'outillage sur un cas réel ; cette carte le fournit.
- **La longue traîne des 6 480 intitulés non classés.** Ranger le dictionnaire n'est pas l'objet :
  5 439 de ces termes n'apparaissent qu'une fois, aucun rangement manuel ne les rattrapera. Le remède
  est `API-037`, pas un import plus malin.
- **Le statut `DEPRECIE` des libellés**, posé par l'écran d'administration
  (`LabelReviewProcessor`) : il reste le geste **humain** de dépréciation, il ne devient pas le
  mécanisme de retrait par le fichier. Les deux cohabitent sans se croiser.
- **L'écart entre les consommateurs sur `status`** — la recherche et l'auto-complétion excluent
  `DEPRECIE`, la reconnaissance d'intitulé ne le regarde pas. C'est un défaut réel, constaté au
  cadrage, mais il ne bloque pas cette tâche puisqu'elle passe par `deleted_at`. Signalé, non traité.
- **Un ordonnancement automatique de la reprojection en production.** Elle reste une commande, et le
  pipeline quotidien la déclenchera ou non quand la question se posera.
- **La régénération d'`api/openapi.json`** : aucune surface d'API n'est touchée.

## Décisions de cadrage

- **Retrait par `deleted_at`, pas par `status`.** Trois consommateurs lisent les libellés : la
  recherche (`CandidateRepository::applyFilters`), l'auto-complétion (`SuggestionFinder`) et la
  reconnaissance d'intitulé à l'ingestion (`JobConceptMap::load()`). Les trois excluent
  `deleted_at IS NULL` ; seuls les deux premiers excluent `DEPRECIE`. Un libellé déprécié
  continuerait donc de rattacher des profils à un concept dont on vient de le retirer — le retrait
  serait à moitié appliqué, et la moitié manquante est justement celle qui produit le défaut.
- **Le retrait est borné aux concepts que le fichier décrit.** Un concept absent du fichier garde ses
  libellés intacts. Le silence ne vaut pas retrait : une taxonomie tronquée ou un sac oublié ne doit
  pas vider la base au premier import.
- **Pas de garde-fou par seuil.** Un import qui refuserait au-delà de X % de retraits demanderait un
  seuil arbitraire et une option de contournement. Les compteurs annoncés et `--dry-run` couvrent le
  besoin : le retrait se voit avant d'être appliqué, et il se relit dans le `git diff` du fichier.
- **La reprojection lit la réplica, jamais Jarvi.** `candidate_jarvi.work_experiences` conserve les
  positions dans la forme même que reçoit l'ingestion : la reprojection est donc rejouable hors
  ligne, sans clé, autant de fois qu'on veut.
- **Elle recalcule les métiers exercés et le concept du poste actuel, rien d'autre.** Ce sont
  exactement les deux projections que le dictionnaire détermine. L'intitulé, l'employeur, la
  certitude et l'ancienneté du poste actuel restent tels que l'ingestion les a posés : ils ne
  dépendent pas du vocabulaire, et les recalculer ferait dériver une durée mesurée à une autre date.
- **La règle de pose est extraite, pas dupliquée.** Deux lectures de la même règle finissent par
  diverger — le dépôt le sait déjà, `CriterionMatcher` porte l'avertissement en tête de fichier. La
  reprojection appelle donc le même objet que l'ingestion.
- **L'enchaînement dans `make dev-vocabulary-refresh`** : c'est l'oubli de la seconde commande qui
  rendait la correction invisible. Corriger la taxonomie doit produire un effet observable sans avoir
  à connaître une commande de plus.
- **La reprojection est un objet de domaine, la commande n'en est que le pilote.** Écrite d'abord
  dans la commande, elle y manipulait `Candidate` et `VocabularyConcept` — deptrac l'a refusée, et il
  a raison : `Command` ne voit que `Domain` et `Enum`. La boucle, les lots et le rapport vivent donc
  dans `VocabularyReprojector` ; la commande lit les options, branche la barre d'avancement et
  affiche le compte rendu. C'est aussi ce qui la rend appelable ailleurs qu'en console.
- **Écart au cadrage — la règle extraite est COMPOSÉE dans `ProfileIngestor`, pas injectée.**
  Le périmètre annonçait un objet dédié « appelé par l'ingestion et par la reprojection », ce qui
  laissait entendre une injection par le constructeur. Elle aurait imposé un douzième argument à
  douze points de construction manuelle dans `JarviIngestionTest` et `CandidateJarviSubmissionTest`,
  chacun devant reconstruire une seconde fois l'expression
  `new TitleConceptResolver(new JobConceptMap($em->getConnection()))`. Le collaborateur n'est pas une
  politique qu'un appelant choisit : c'est la décomposition d'une méthode privée de cette classe, et
  ses deux dépendances sont déjà les siennes. Il est donc construit dans le constructeur, et le
  contrat public de l'ingestion ne bouge pas. La reprojection, elle, le reçoit par injection.

## Règles et cas limites

- **Import rejoué sans changement** : zéro retrait, zéro restauration. Vérifié avant toute autre
  chose — c'est la preuve que le mécanisme est inerte tant qu'on ne lui demande rien.
- **Libellé retiré puis rendu** : la même ligne revient, avec son `uuid` et sa date de création
  d'origine. Aucune ligne n'est créée en double.
- **Concept sans aucun synonyme dans le fichier** : tous ses libellés sont retirés. C'est cohérent —
  le fichier dit qu'il n'en revendique aucun — mais le concept devient alors introuvable par la
  recherche, qui ne compare qu'aux libellés et au libellé canonique. À ne pas confondre avec un bug.
- **Reprojection sur un profil sans réplica** : ignoré, aucune ligne posée.
- **Volume** : 3 053 profils dans le vivier de développement. L'écriture se fait par lots de 200,
  pour la même raison que l'ingestion — l'Unit of Work retient chaque entité et une copie de ses
  données d'origine.
- **`--dry-run` de la reprojection** : les compteurs sont calculés, rien n'est écrit.
- **Concurrence** : la reprojection écrit sur les mêmes lignes que l'ingestion. Les deux ne doivent
  pas tourner en même temps ; rien ne l'interdit techniquement, la carte le signale.
- **Le catalogue des postes est chargé une fois puis gardé en mémoire** (`JobConceptMap`). Une
  commande de console vit dans son propre processus, elle le voit donc toujours frais — l'import et
  la reprojection enchaînés par `make dev-vocabulary-refresh` sont bien deux processus distincts.
  **Découvert au test** : dans un même processus, la reprojection lancée après un import travaille
  sur le dictionnaire d'AVANT. Le test redémarre donc le noyau à chaque reprojection, pour éprouver
  la topologie réelle et non un cache que la production n'a pas.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` — le tableau des commandes du pipeline
  ne connaît pas `app:vocabulary:reproject`, et la ligne « `make dev-vocabulary-refresh` enchaîne
  `enrich` puis `import` » devient fausse.
- `dev-book/resources/taxonomie/README.md` — le cycle du dictionnaire décrit un import qui ne fait
  qu'ajouter ; le détachement d'un libellé y manque.

## Vérification

**Expérience sur le vivier réel** (3 053 profils, stack du worktree `api`) — un seul libellé,
`solution architect`, retiré du concept `poste-024` sur une **copie** du fichier ; le fichier
versionné n'a pas bougé.

```
app:vocabulary:import --file=/app/var/taxonomy-sans-solution-architect.json
  0 concepts créés, 860 mis à jour ; 0 libellés créés, 3357 mis à jour, 1 retirés, 0 rendus
```

Les trois consommateurs, mesurés par appel HTTP réel authentifié en `entreprise@showcase-recruiter-webapp-saas.example`
(`ROLE_COMPANY_OWNER`) :

| Consommateur | Avant | Après retrait | Après restitution |
| --- | --- | --- | --- |
| Auto-complétion `GET /v1/suggestions?type=JOB&q=solution` | `Architecte (IT/logiciel)` | `{"items":[],"total":0}` | `Architecte (IT/logiciel)` |
| Recherche `GET /v1/search/candidates?job=solution architect` | `"total":37` | `"total":0` | `"total":37` |
| Reconnaissance d'intitulé, après reprojection — `?job=architecte` | `"total":37` | `"total":31` | `"total":37` |

La troisième ligne est celle qui départageait `deleted_at` et `status` : six profils n'atteignaient
ce concept que par le libellé retiré, et la reprojection les a détachés. Une dépréciation par
`status` les aurait laissés en place.

**La restitution rend la ligne, elle n'en crée pas** :

```
       label        |                 uuid                 | vivant
 solution architect | 019fc2ec-74c4-7598-ac6d-888f3e1e9cd7 |   t     ← l'UUID d'avant le retrait
```

**Inerte tant qu'on ne lui demande rien** — import rejoué sur le fichier versionné :
`0 retirés, 0 rendus`. Et `app:vocabulary:reproject --limit=50 --dry-run` :
`[dry-run] 50 profils parcourus, 0 rattachement(s) … posé(s) ou retiré(s)`.

**Tests impactés** — `php bin/phpunit --filter VocabularyReprojectionTest --testdox` :

```
✔ A label dropped from the file stops attaching profiles
✔ A label given back to the file comes back on its own row
✔ A concept absent from the file keeps its labels
✔ The dictionary only moves profiles once reprojected
✔ Reprojection dry run writes nothing

OK (5 tests, 17 assertions)
```

Et la non-régression de l'extraction hors de `ProfileIngestor` —
`--filter 'JarviIngestionTest|CandidateJarviSubmissionTest|CandidateJobTenureTest|CandidateJobExpansionTest'` :
`OK (29 tests, 109 assertions)`.

**Frontières de couche** — `composer deptrac` : `Violations 0`. La première écriture en comptait deux,
la commande manipulant les entités directement ; c'est ce refus qui a fait naître
`VocabularyReprojector`.

**Contrat** — `api:openapi:export` relancé après vidage du cache : `api/openapi.json` **inchangé**,
aucune surface d'API n'étant touchée.

**Non lancé, et pourquoi** — `make dev-vocabulary-refresh` en entier : sa première étape,
`app:vocabulary:enrich`, consomme la clé LLM, réservée à un usage rare et volontaire. Les deux
étapes que cette tâche ajoute ou touche ont été jouées séparément ci-dessus.

## Livré

- `api/src/Domain/Vocabulary/VocabularyImporter.php` (M) — retrait des libellés non revendiqués,
  restauration de ceux qui reviennent, filtre soft delete levé le temps de l'import ; `importSac`
  décomposé en `upsertConcept` + `syncLabels`.
- `api/src/Domain/Vocabulary/VocabularyImportReport.php` (M) — `labelsRemoved`, `labelsRestored`.
- `api/src/Command/VocabularyImportCommand.php` (M) — les deux compteurs dans le compte rendu.
- `api/src/Entity/AbstractEntity.php` (M) — `restore()`, réciproque de `softDelete()`.
- `api/src/Domain/Jarvi/JobExperienceSynchronizer.php` (+) — la pose des métiers exercés, extraite.
- `api/src/Domain/Jarvi/ProfileIngestor.php` (M) — délègue à l'objet extrait.
- `api/src/Domain/Jarvi/VocabularyReprojector.php` (+) — la reprojection elle-même : liste des
  profils, lots, recalcul des deux projections.
- `api/src/Domain/Jarvi/VocabularyReprojectReport.php` (+) — son compte rendu.
- `api/src/Command/VocabularyReprojectCommand.php` (+) — `app:vocabulary:reproject`, pilote.
- `Makefile.dev` (M) — `dev-vocabulary-refresh` enchaîne `reproject`.
- `api/tests/Functional/VocabularyReprojectionTest.php` (+) — cinq tests.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` (M) — la commande dans le tableau du
  pipeline, la règle du retrait, et le raccourci quand seul le dictionnaire a changé.
- `dev-book/resources/taxonomie/README.md` (M) — section « Comment ça se corrige ».

## Reste à faire (hors périmètre)

- **`API-036`** exerce cet outillage sur les concepts architecte. Rien n'est visible pour un
  recruteur tant qu'elle n'est pas livrée : cette tâche ne corrige aucun contenu.
- **Le statut `DEPRECIE` reste ignoré par la reconnaissance d'intitulé.** Un libellé déprécié depuis
  l'écran d'administration continue de rattacher des profils, là où la recherche et l'auto-complétion
  l'excluent. Constaté au cadrage, hors périmètre, **non cardé**.
- **Doc périmée non corrigée** : `dev-book/resources/taxonomie/README.md` affirme encore que la
  taxonomie n'est lue par aucune route et que « la recherche ne s'en sert pas encore » — faux depuis
  la task `125`. Deux lignes au-dessus du bloc édité, hors périmètre de cette tâche.

## Définition de terminé

- [x] Un libellé retiré du fichier disparaît de la recherche, de l'auto-complétion **et** de la
      reconnaissance d'intitulé à l'ingestion.
- [x] Le même libellé rendu au fichier revient sur la même ligne, sans doublon.
- [x] Un import rejoué à l'identique annonce zéro retrait et zéro restauration.
- [x] `app:vocabulary:reproject` déplace un profil d'un concept vers un autre après un changement de
      dictionnaire, sans aucun appel à Jarvi, et l'annonce en compteurs.
- [x] La règle de pose des métiers exercés n'existe qu'à un seul endroit, appelé par l'ingestion et
      par la reprojection.
- [x] `make dev-vocabulary-refresh` enchaîne les trois étapes.
- [x] Tests fonctionnels sur l'import et sur la reprojection.
- [x] Les deux pages de documentation à jour.
