# TASK API-031 — api : les mots-clés de démonstration viennent du vivier

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — les 63 recherches de démonstration portent désormais les
  compétences que leurs profils portent vraiment, aucune n'est vide, aucune ne demande une langue.
  De 7 à 234 profils par carte, moyenne 41. 15 tests, 569 assertions.
- **Type** : Backend API — données de démonstration, aucune surface publique touchée.
- **Tests** : unitaires sur les invariants du catalogue — deux critères au minimum, un métier en
  tête, aucune langue en mot-clé, pas deux entrées identiques, les critères qui resserrent restent
  minoritaires. Ces règles ne se voient pas en lisant une carte : il faut les vérifier sur les
  soixante entrées à la fois, et c'est justement ce qu'une relecture humaine rate.
- **Réf** : `API-029` — la commande d'amorçage que cette tâche affine · `DemoSearchCatalog` ·
  `CandidateJobExperience` · `CandidateJarvi` (les compétences)

## Contexte

Les recherches de démonstration d'`API-029` remplissaient bien la grille, mais leurs critères
sonnaient faux à la lecture. Trois défauts, relevés en regardant l'écran :

- **Une carte à zéro profil**, le brouillon sans critère. Elle ne montrait rien de la mise en page et
  faisait douter de la recherche plutôt que du jeu de démonstration.
- **Des mots-clés inventés au jugé** — « conception », « process », « cao » — plausibles pour le
  métier, mais sans rapport avec ce que les profils déclarent réellement.
- **Le risque des langues** : « anglais » est la compétence la plus portée du vivier après les
  outils bureautiques. En mot-clé, elle ramènerait un profil sur quatre de n'importe quel métier et
  ferait passer une recherche ciblée pour une recherche large.

## Périmètre

- **Les mots-clés sortent des compétences réellement déclarées** par les profils rattachés à chaque
  métier, mesurées sur le vivier.
- **Le brouillon sans critère disparaît** du catalogue.
- **Quatre recherches nouvelles** entrent au passage — `Consultant en management`, `Product Owner`,
  `Responsable qualité`, `Employé de restauration` — le catalogue passe de 61 à 63.
- **Un test unitaire tient les invariants du catalogue.**

## Hors périmètre

- **Le mécanisme d'amorçage** — la commande, le seeder, les compteurs : `API-029`, inchangée.
- **L'écran.** Aucune ligne de `webapp/`.
- **Le vocabulaire métier lui-même.** Cette tâche lit ce que l'ingestion a produit ; elle ne
  rattache rien et ne corrige aucun libellé.

## Décisions de cadrage

- **La spécificité prime sur la fréquence.** Les compétences les plus portées d'un métier sont les
  plus creuses : « communication », « management », « microsoft office » sont sur presque tous les
  profils du vivier et ne disent rien de ce qu'on cherche. Celles retenues sont nettement plus
  présentes chez ce métier qu'ailleurs — `catia` et `solidworks` chez l'ingénieur mécanique,
  `déclaration fiscale` chez le comptable, `terraform` et `jenkins` chez l'ingénieur DevOps.
- **Mais la spécificité seule vide les cartes.** Les mots-clés se relient en OU entre eux, et en ET
  avec le métier : quatre compétences rares ajoutées à un métier le rétrécissent. La plus grosse
  carte est tombée de 289 à 77 profils. Les métiers massifs reçoivent donc aussi une compétence
  **fréquente et liée au métier** — `gestion de projet` pour un chef de projet, `java` pour un
  développeur —, ce qui rend le volume sans diluer le propos.
- **Aucune langue en mot-clé**, et la règle est tenue par un test plutôt que par la vigilance :
  c'est le genre d'ajout qu'on refait sans y penser six mois plus tard.
- **Un métier pauvre garde des mots-clés larges.** Quatre cartes restent sous la dizaine de profils
  — le vivier ne porte pas mieux pour ces métiers-là. Une recherche pointue qui ramène sept profils
  reste une recherche vraisemblable ; une qui en ramène zéro, non.

## Documentation impactée

Aucune. La commande, ses options et sa place dans le pipeline sont inchangées — seul le contenu du
catalogue bouge, et il est décrit dans son propre docblock.

## Définition de terminé

- [x] Aucune recherche du catalogue ne ramène zéro profil.
- [x] Aucun mot-clé n'est une langue.
- [x] Les mots-clés de chaque recherche sont des compétences réellement portées par les profils de
      son métier.
- [x] Les volumes restent démonstratifs : au moins une carte au-dessus de deux cents profils.
- [x] Les invariants du catalogue sont tenus par des tests, pas par la relecture.

## Vérification

**Sur une copie du vivier de développement** (3 052 profils exposables), en repartant d'une base
propre à chaque essai :

| | Avant | Mots-clés spécifiques seuls | Retenu |
| --- | --- | --- | --- |
| Carte la plus large | 289 | 77 | **234** |
| Carte la plus étroite | 0 | 3 | **7** |
| Moyenne | — | 26 | **41** |
| Cartes à zéro profil | 1 | 0 | **0** |

**Les plus fournies** : Chef de projet 234, Manager 210, Développeur logiciel 135, Commercial terrain
122, Consultant 120, Responsable 118, Commercial 117, Business Developer 95, Auditeur 75.

**À l'écran**, sur la stack de l'espace entreprise : 58 cartes servies au compte principal, chaque
titre suivi de ses étiquettes de compétences — « Ingénieur DevOps · terraform » avec `ansible`,
`jenkins` et « +2 critères » ; « Data Engineer · apache airflow » avec `azure data factory` et
`google bigquery ».

**Tests : 15, 569 assertions, vert.** Sept unitaires sur les invariants du catalogue, quatre sur les
bornes de l'avancement, quatre fonctionnels sur la commande. Style, PHPStan et deptrac sans écart.

**Un test a rattrapé une régression que la relecture n'aurait pas vue** : le candidat fabriqué par le
test fonctionnel ne portait aucune des compétences nouvellement demandées, et son jeu est tombé à
zéro. Il lit désormais les mots-clés sur la définition au lieu de les supposer.

## Livré

- `api/src/Domain/Search/Demo/DemoSearchCatalog.php` **(M)** — 63 recherches, leurs mots-clés, et le
  motif de leur choix.
- `api/tests/Unit/DemoSearchCatalogTest.php` **(+)** — les invariants.
- `api/tests/Functional/DemoSearchSeedCommandTest.php` **(M)** — le candidat de test satisfait tous
  les critères de l'entrée visée, et vise une recherche active plutôt qu'une close.
- `dev-book/tasks/API-029-…md` **(M)** — le catalogue qu'elle décrit a changé.
