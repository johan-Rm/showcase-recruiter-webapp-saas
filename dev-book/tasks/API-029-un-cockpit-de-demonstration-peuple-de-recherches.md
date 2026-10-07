# TASK API-029 — api : un cockpit de démonstration peuplé de recherches

- **Créée le** : 2026-08-05.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — `app:searches:seed-demo` pose 63 recherches sur le vivier
  présent, tous états de carte représentés, aucune carte vide, et se rejoue sans rien dupliquer.
  15 tests, 569 assertions. Vérifiée sur une copie du vivier de développement (3 052 profils
  exposables) : de 7 à 234 profils par carte, moyenne 41, et 63 déjà présentes au second passage.
- **Type** : Backend API — une commande d'amorçage, aucune surface publique touchée.
- **Tests** : fonctionnels sur la commande — elle crée le catalogue, elle se rejoue sans dupliquer,
  elle ne touche pas aux recherches qu'elle n'a pas créées, et les quatre compteurs de la carte
  valent ce que le catalogue annonce. Une commande d'amorçage ne se prouve que par son effet en
  base. S'y ajoutent deux séries unitaires : les trois bornes de l'avancement, qui décident si la
  grille est croyable, et les invariants du catalogue — deux critères au minimum, aucune langue en
  mot-clé, pas deux entrées identiques. Ces derniers ne se voient pas en lisant une carte : il faut
  les vérifier sur les soixante entrées à la fois.
- **Réf** : `SavedSearch` · `SearchResultMembership` · `SearchProfileReview` · `SearchView` ·
  `Favorite` · `SearchMembershipRecalculator` · `CandidateJobExperience` (les métiers exercés d'où
  sortent les mots-clés) · `SeedTeamAccountsCommand` (le patron d'une commande
  d'amorçage idempotente) · `SavedSearchOutput` (les quatre compteurs) ·
  [carte-recherche-rail-v1.html](../../ui-ux-design/draft/carte-recherche-rail-v1.html) (les états
  que la grille doit pouvoir montrer)

## Contexte

L'accueil de l'espace entreprise affiche une **grille de cartes**, une par recherche. Chaque carte
annonce quatre nombres — combien de profils, combien de nouveaux depuis le dernier passage, combien
de vus, combien de retenus — et peut être **éteinte** (recherche close) ou **vide** (brouillon sans
critère).

Rien ne permet aujourd'hui de voir cette grille autrement qu'à vide. Les fixtures de démo créent
**une** recherche, qui trouve **un** profil : le rail de la carte affiche « 1 profil », jamais la
jauge d'avancement, jamais un badge de nouveautés. Juger la mise en page, la hiérarchie visuelle ou
la lisibilité d'une grille de vingt cartes est donc impossible — et la maquette qui la dessine ne
peut être confrontée à rien.

Deux obstacles interdisent de régler ça dans les fixtures Doctrine :

1. **`doctrine:fixtures:load` purge la base.** Le vivier de développement compte ~3 000 profils
   **importés de Jarvi**, pas créés par les fixtures, qui n'en créent que trois. Y ajouter des
   recherches signifierait détruire le vivier à chaque chargement, et retomber sur des cartes à un
   profil.
2. **Les critères déclaratifs ne trouvent personne sur ce vivier.** Secteur souhaité, métier
   souhaité, disponibilité, horizon de retour : renseignés sur les trois profils de démo, vides sur
   les ~3 000 importés. Ce qui ramène du monde, ce sont le métier exercé, les mots-clés, les années
   d'expérience, le niveau d'études, le pays et la langue.

## Périmètre

- **`app:searches:seed-demo`** — commande d'amorçage, sur le patron de `app:accounts:seed-team` :
  additive, rejouable sans dupliquer, refusée en production sauf `--force`.
- **`DemoSearchCatalog`** — 63 recherches, réparties sur les familles de métiers réellement
  présentes dans le vivier : administration, commerce, finance, industrie, logistique, ressources
  humaines, enseignement, restauration, bâtiment, juridique, et une minorité de métiers
  informatiques.
- **Les mots-clés sont ceux que portent réellement les profils du métier**, choisis pour leur
  spécificité et non pour leur fréquence — `catia` et `solidworks` chez l'ingénieur mécanique,
  `déclaration fiscale` chez le comptable, `terraform` chez l'ingénieur DevOps. Aucune langue :
  « anglais » est sur un profil sur quatre et transformerait une recherche ciblée en recherche
  large.
- **`DemoSearchSeeder`** — pose les recherches, puis **délègue le jeu de résultats au recalculateur
  existant** : les volumes affichés sont ceux que la vraie recherche produirait, pas des nombres
  écrits à la main.
- **Les quatre compteurs sont fabriqués sur le jeu réel** : les dates d'entrée sont étalées dans le
  passé et une poignée ramenée après le repère de lecture (les nouveautés), les profils vus reçoivent
  leur ligne de revue, les retenus leur favori.
- **Les états de la grille sont tous représentés** : recherches en cours à tous les stades
  d'avancement, closes, et partagées par un collègue (donc en lecture seule).
- **Aucune carte ne ramène zéro profil.**

## Hors périmètre

- **Les fixtures Doctrine** — inchangées. La recherche « Finance France vers Casablanca » y reste :
  trois tests fonctionnels s'appuient sur elle.
- **Les 26 recherches d'essai** présentes dans la base de développement : ni supprimées, ni
  modifiées, ni fermées. Le catalogue pose au moins deux critères par recherche, là où les essais
  n'en portent qu'un — aucune ne peut donc être confondue avec une entrée du catalogue.
- **L'écran.** Aucune ligne de `webapp/`.
- **Une cible `make`** pour lancer la commande : le `Makefile` appartient à l'outillage.
- **La production.** Cette commande amorce une démonstration ; elle n'a rien à y faire.

## Décisions de cadrage

- **Une commande plutôt que des fixtures**, parce que la purge est le vrai sujet : le vivier importé
  est la seule chose qui donne du volume aux cartes, et il ne survit pas à un chargement de fixtures.
- **Le jeu de résultats vient du recalculateur, jamais d'un nombre écrit.** Une carte qui annoncerait
  « 225 profils » sans que la Table en serve 225 ferait chercher un bug qui n'existe pas — le
  commentaire des fixtures actuelles dit déjà pourquoi.
- **Reconnaître ses propres recherches par leurs critères, pas par leur nom.** Le nom d'une recherche
  est composé automatiquement à partir de ses critères et vaut le plus souvent la chaîne vide en
  base ; les critères, eux, l'identifient exactement. C'est aussi ce qui protège les essais de
  l'utilisateur.
- **Aucune carte vide.** Une recherche sans critère se voulait la carte du brouillon ; elle a été
  retirée. Elle n'apprenait rien sur la mise en page, et une grille de démonstration qui s'ouvre sur
  un zéro fait douter de la recherche plutôt que du jeu de données.
- **Les mots-clés se choisissent sur la spécificité, pas sur la fréquence.** Les compétences les
  plus portées d'un métier sont les plus creuses — « communication », « management », « microsoft
  office » sont sur presque tous les profils du vivier. Celles retenues sont nettement plus
  présentes chez ce métier qu'ailleurs. Sur les métiers massifs s'ajoute une compétence fréquente
  **mais liée au métier** (`gestion de projet` pour un chef de projet, `java` pour un développeur) :
  sans elle, l'enrichissement faisait tomber la plus grosse carte de 289 à 77 profils.
- **Aucun tirage au sort.** L'avancement de chaque carte est dérivé de son rang dans le catalogue :
  deux exécutions produisent la même grille, et une capture d'écran reste comparable à la suivante.
- **Les trois compteurs se bornent l'un l'autre, et c'est une règle testée à part.** Les arrivants
  récents sortent du calcul de la part parcourue, et on ne retient jamais plus de profils qu'on n'en
  a vus. Une première version l'ignorait : la grille annonçait huit profils retenus pour un seul vu,
  et vingt-sept profils à la fois nouveaux et déjà parcourus. Une carte qu'on ne croit pas ne sert à
  rien pour juger un écran.
- **Rejouer complète, et ne retranche pas.** Après un import qui agrandit le vivier c'est le
  comportement voulu : le parcours déjà fait ne s'efface pas. Après une refonte du catalogue, les
  compteurs peuvent en revanche dépasser la nouvelle progression — c'est alors la base qu'on reprend,
  pas la commande.

## Règles et cas limites

- **Une recherche sans critère ne matérialise rien** : le recalculateur refuse de traiter une liste
  de critères vide, et c'est ce qui donne la carte du brouillon — 0 profil, pas le vivier entier.
- **Un métier rare ne reçoit qu'un critère large.** Croiser « Juriste » (19 profils) avec un mot-clé
  rare produit une carte à zéro, qui n'apprend rien sur la mise en page. Les mots-clés étroits sont
  réservés aux métiers à gros volume.
- **Les critères déclaratifs sont écartés du catalogue** — secteur souhaité, disponibilité, horizon
  de retour, séjour au Maroc. Ils sont renseignés sur les trois profils des fixtures et vides sur les
  ~3 000 importés : une carte bâtie dessus annoncerait un profil.
- **Une recherche close n'annonce aucune nouveauté**, `API-028` l'éteint côté serveur ; le compte
  rendu de la commande affiche donc zéro, quelles que soient les entrées récentes de son jeu.
- **Sans collègue dans l'entreprise**, les recherches prévues en lecture seule reviennent au
  recruteur ciblé : on ne peut pas recevoir sa propre recherche, et la commande le dit au lieu de
  produire une carte muette.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` — la commande rejoint le tableau
  « Hors périmètre données », à côté de `app:accounts:seed-team`, avec la dépendance qui la
  gouverne : elle suppose le vivier déjà chargé et ne crée aucun profil.

`api/openapi.json` n'est **pas** concerné : aucune ressource, aucun DTO, aucune opération n'est
touché — une commande console n'a pas de surface publique. Aucune page décrivant l'écran n'est rendue
fausse : la grille de l'accueil entreprise ne change pas, elle se remplit.

## Définition de terminé

- [x] `app:searches:seed-demo` crée la grille sur le vivier présent, sans rien purger.
- [x] Au moins cinquante recherches, portant des métiers largement hors informatique.
- [x] Le jeu de résultats de chaque recherche est produit par le recalculateur d'appartenance.
- [x] Les quatre compteurs de la carte sont peuplés : volume, nouveautés, profils vus, profils
      retenus.
- [x] Les trois compteurs d'avancement se bornent l'un l'autre — les arrivants récents hors de la
      part parcourue, les retenus jamais au-delà des vus.
- [x] Tous les états de carte sont représentés : avancement de zéro à complet, closes, reçues d'un
      collègue.
- [x] Aucune recherche ne ramène zéro profil.
- [x] Aucun mot-clé n'est une langue.
- [x] Relancée, la commande ne crée aucun doublon.
- [x] Les recherches qu'elle n'a pas créées sont intactes — nom, partage, clôture, favoris, revues.
- [x] Elle refuse de s'exécuter en production sans `--force`.

## Vérification

**Sur une copie du vivier de développement** (3 052 profils exposables), restaurée dans la base de la
lane pour éprouver la commande sur des volumes réels :

| | Premier passage | Second passage |
| --- | --- | --- |
| Recherches créées | **63** | **0** |
| Déjà présentes | 0 | **63** |

**Aucune carte vide, et des volumes qui tiennent** : de **7** profils pour la plus étroite à **234**
pour la plus large, moyenne **41**, et quatre cartes seulement sous la dizaine. Les plus fournies :
Chef de projet 234, Manager 210, Développeur logiciel 135, Commercial terrain 122, Consultant 120,
Responsable 118, Commercial 117.

**Ce que l'enrichissement des mots-clés a coûté, puis rendu.** Posés seuls, les mots-clés spécifiques
resserrent — ils s'ajoutent en OU entre eux, mais en ET avec le métier :

| | Avant enrichissement | Mots-clés spécifiques seuls | Avec une compétence-pivot |
| --- | --- | --- | --- |
| Carte la plus large | 289 | **77** | **234** |
| Cartes à zéro profil | 1 (le brouillon) | 0 | 0 |

**La correction des bornes**, sur trois cartes qui mentaient — les nouveaux arrivants comptés comme
vus, les retenus dépassant les vus :

| Carte | Avant | Après |
| --- | --- | --- |
| Analyste financier | 8 profils, **1 vu, 8 retenus** | 8 profils, 1 vu, **1 retenu** |
| Réceptionniste | 27 profils, **27 nouveaux, 21 vus** | 27 profils, 27 nouveaux, **0 vu** |
| Chargé de recrutement | 19 profils, **10 vus, 14 retenus** | 19 profils, 10 vus, **10 retenus** |

**Les recherches d'essai de la base sont intactes** : aucune close, aucune partagée, aucun favori ni
aucune revue ajoutés — relevé colonne par colonne avant et après les deux passages.

**À l'écran**, sur la stack de l'espace entreprise : 58 cartes servies, titres de 52 caractères au
plus, étiquettes de compétences sous chaque titre, pli « 5 recherches closes » en bas de grille.

**Tests : 15, 569 assertions, vert.** Sept unitaires tiennent les invariants du catalogue — deux
critères au minimum, un métier en tête, aucune langue en mot-clé, pas deux entrées identiques, les
critères qui resserrent restent minoritaires, tous les états représentés. Quatre unitaires tiennent
les bornes de l'avancement. Quatre fonctionnels tiennent la commande — la grille et ses états,
l'absence de doublon au second passage, une recherche d'essai laissée intacte, et les quatre
compteurs d'une carte confrontés à ce que le catalogue prévoit pour son rang.

**Style, PHPStan et deptrac** sur les fichiers touchés : 0 correction, 0 erreur, 0 violation de
frontière. Suite complète `make api-quality` : **443 tests, 2 263 assertions, vert**.

## Livré

- `api/src/Command/SeedDemoSearchesCommand.php` **(+)** — l'entrée console, le refus en production,
  le tableau de compte rendu.
- `api/src/Domain/Search/Demo/DemoSearchCatalog.php` **(+)** — les 61 recherches et les sept
  progressions, dérivées du rang pour rester reproductibles.
- `api/src/Domain/Search/Demo/DemoSearchDefinition.php` **(+)** — une recherche du catalogue,
  identifiée par ses critères.
- `api/src/Domain/Search/Demo/DemoSearchProgress.php` **(+)** — les trois bornes, et la règle qui les
  ordonne.
- `api/src/Domain/Search/Demo/DemoSearchSeeder.php` **(+)** — pose les recherches, délègue le jeu au
  recalculateur, fabrique le passé que ce recalcul ne peut pas produire.
- `api/src/Domain/Search/Demo/DemoSearchLine.php` **(+)** · `DemoSearchReport.php` **(+)** — le
  compte rendu, relu sur les dépôts qui servent la carte.
- `api/tests/Unit/DemoSearchProgressTest.php` **(+)** · `api/tests/Functional/DemoSearchSeedCommandTest.php` **(+)**.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` **(M)**.

## Reste à faire (hors périmètre)

- **Le rail de la carte, côté écran** : [`Card.vue`](../../webapp/spaces/entreprise/app/components/search/Card.vue)
  n'affiche que le nombre de profils. Ni le badge de nouveautés, ni la jauge « vus / retenus », ni
  l'extinction d'une recherche close ne sont implémentés, alors que l'API sert les quatre nombres
  depuis `API-027` et `API-028`. C'est l'espace entreprise.
- **Une cible `make`** pour lancer la commande sans passer par `dev-api CMD=…` : outillage.
