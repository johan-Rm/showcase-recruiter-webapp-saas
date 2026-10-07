# TASK API-042 — les profils de démo ressemblent enfin à de vrais profils

- **Créée le** : 2026-08-13.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff` — carte trouvée sans session inscrite, reprise
  sur demande explicite de l'humain le 2026-08-14.
- **Statut** : ✅ fait le 2026-08-14 — six profils de démo complets des deux côtés, chacun rattaché à
  un métier reconnu, et une recherche par poste les remonte enfin.
- **Type** : feat (données de démonstration).
- **Tests** : la suite fonctionnelle existante doit rester verte — les fixtures la nourrissent. Un
  test ajouté vérifie qu'un profil de démo est bien trouvé par le filtre « poste », ce qui est
  impossible aujourd'hui.
- **Réf** : `api/src/DataFixtures/AppFixtures.php` · `ENT-112` (le tiroir qui a besoin de ces
  profils) · `JobProjectionSynchronizer` (comment l'import rattache les métiers) ·
  `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`.

## Contexte

**Aucun profil de la base ne ressemble à ce qu'un recruteur verra en production.** La base en
contient deux familles, et chacune est vide là où l'autre est pleine.

Les profils **importés de Jarvi** — près de quatre mille — ont un vrai parcours : poste actuel,
employeur, niveau d'études, huit expériences, quarante compétences, et surtout un **métier reconnu
par le dictionnaire**, celui qui fait fonctionner le filtre « poste ». Mais ils n'ont ni horizon de
retour, ni disponibilité, ni pied-à-terre, ni présence au Maroc, ni projet de retour : ces
informations viennent du questionnaire que le candidat remplit chez nous, et Jarvi ne les fournit
pas.

Les **trois profils de démo**, eux, portent tout le projet Maroc — et rien d'autre. Une expérience,
une formation, deux compétences, pas de poste actuel, pas de niveau d'études, et **zéro métier
reconnu** :

```
                       métiers   intitulés   secteurs
un profil importé          1          8          2
nos profils de démo        0          0          0
```

Conséquence mesurée : une recherche sur le poste « Développeur logiciel » remonte trois cent
cinquante-deux profils, **et aucun profil de démo n'en fait partie** — alors que l'un d'eux est
développeur. Ils sont introuvables par le chemin que le produit met en avant.

Ce qui manque n'est donc pas un jeu de données plus gros : c'est **au moins un profil qui cumule les
deux familles**, pour qu'un écran puisse être éprouvé de bout en bout.

## Périmètre

- **Enrichir les trois profils de démo existants** : poste et employeur actuels, niveau d'études,
  plusieurs expériences datées, plusieurs formations, langues, un jeu de compétences crédible.
- **Leur donner un métier reconnu**, ses intitulés et ses secteurs d'expérience — les trois
  rattachements que l'import produit et que les fixtures ne posent pas.
- **Ajouter deux ou trois profils complets**, sur des métiers différents : un technique, un finance,
  un commercial. Tous exemplaires, tous bien remplis.
- **Les contenus payants existent** : CV, compte rendu d'entretien, e-mail et téléphone.
- **Au moins un contenu déjà dévoilé** pour l'entreprise de démonstration, afin que les deux états
  — verrouillé et dévoilé — se voient côte à côte sans dépenser un crédit à chaque rechargement.

## Hors périmètre

- **Les cas particuliers** : le profil presque vide, celui déjà rencontré en entretien, celui
  actuellement au Maroc. Écartés à voix haute, ils feront une carte à part si le besoin se confirme.
- **Les profils importés**, qu'on ne modifie pas.
- **Le pipeline d'enrichissement** et le dictionnaire de métiers, qui ne changent pas.
- **Tout écran.** Le tiroir est l'objet de `ENT-112`, sur une autre lane.

## Décisions de cadrage

### Enrichir et ajouter, plutôt que repartir de zéro

Un jeu neuf serait plus cohérent, et casserait l'identifiant stable
`01900000-0000-7000-8000-0000000000c1` — celui que la collection de requêtes et plusieurs tests
désignent nommément. **Les trois existants restent, avec leurs identifiants**, et gagnent un vrai
parcours.

### Les métiers s'écrivent à la main

L'import déduit les métiers des intitulés par `JobProjectionSynchronizer`. Faire appeler ce service
par les fixtures serait fidèle à la réalité — et **leur ferait dépendre du cœur métier**, ce que le
projet leur interdit : le commentaire des fixtures actuelles le dit déjà, à propos des années
d'expérience qu'elles posent en dur plutôt que de les calculer.

Une commande séparée, lancée après le chargement, éviterait la dépendance au prix d'une étape qu'on
oubliera. **Les rattachements sont donc écrits explicitement dans les fixtures**, comme tout le
reste.

**Réserve assumée, à écrire dans le code** : ces rattachements sont figés. Si le dictionnaire de
métiers évolue, ils ne suivront pas tout seuls — un profil de démo pourra désigner un métier qui a
changé de nom. C'est le prix de l'indépendance des fixtures, et il se paie en une ligne à corriger.

### Le dictionnaire manque sur une base fraîche — les fixtures posent les trois concepts

Découvert à l'implémentation, et le cadrage ne l'avait pas vu : **un métier reconnu n'est pas une
chaîne, c'est un lien vers une entrée du dictionnaire**, et ce lien ne peut pas être nul. Or
`make dev-setup` enchaîne schéma → fixtures → comptes → base de test **sans jamais** appeler
`app:vocabulary:import` : sur une base neuve, la table des concepts est vide. La carte demandait donc
un rattachement que sa propre exigence « chargement sur base vide » rendait impossible.

**Les fixtures créent elles-mêmes les trois concepts dont elles ont besoin**, avec les codes réels de
la taxonomie versionnée — `poste-003` Développeur logiciel, `poste-013` Comptable, `poste-008`
Commercial. Ce n'est pas une duplication du dictionnaire : l'importeur retrouve un concept **par son
code avant son libellé**, puis le met à jour. Un `app:vocabulary:import` ultérieur adopte donc ces
trois lignes au lieu d'en créer des jumelles.

Écarté : **charger le dictionnaire dans le bootstrap**. Ça règle un manque plus large — sans lui, la
recherche par poste ne remonte rien en dev, pour aucun profil — mais c'est un autre sujet que des
profils de démo, et il allonge chaque installation. À rouvrir pour lui-même.

Écarté aussi : **sauter le rattachement quand la table est vide**. Sans risque, et sans effet : sur
le chemin le plus courant, les profils de démo resteraient introuvables par le filtre poste.

### Un contenu déjà dévoilé dans le jeu de démonstration

Sans lui, voir l'état dévoilé oblige à dépenser un crédit à la main après chaque rechargement de la
base — c'est-à-dire plusieurs fois par jour en développement. Une ligne de dévoilement pour
l'entreprise de démonstration met les deux états sous les yeux d'emblée.

## Règles et cas limites

- **Les fixtures restent déterministes** : aucune valeur tirée au hasard, aucune date relative là où
  une date fixe suffit. Les périodes de présence au Maroc restent relatives — une présence « à
  venir » déjà passée ne dirait plus rien.
- **Aucune donnée personnelle réelle.** Noms, employeurs et écoles sont inventés, et se lisent comme
  tels.
- **Le profil caché reste caché.** Celui qui n'a pas donné son consentement doit continuer de ne
  jamais sortir d'une recherche : c'est lui qui prouve la règle.
- **Les années d'expérience restent cohérentes** avec les périodes qu'on écrit, même si les fixtures
  ne les calculent pas.
- **Le contrat ne change pas** : aucune propriété ajoutée, `api/openapi.json` inchangé. Si la
  surface bougeait malgré tout, elle se régénère avant le commit.
- **Chargement complet vérifié** : les fixtures doivent passer sur une base vide **et** sur une base
  déjà peuplée par un import Jarvi, qui est le cas réel du poste de développement.

## Documentation impactée

- Le `README.md` de `api/src/DataFixtures/` s'il décrit le jeu de données.
- `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` uniquement si un exemple cité y devient
  faux — la carte ne réécrit pas le cadrage.

## Vérification

**Chargement sur base neuve**, dans une base jetable créée puis supprimée pour ne pas purger les
**3 053 profils importés** du poste de développement — vérifiés intacts après coup.
`doctrine:fixtures:load` va au bout sans erreur.

**Ce que les fixtures posent** : 6 candidats, 3 concepts métier, 6 rattachements, 17 intitulés,
6 secteurs d'expérience, 2 dévoilements.

| Profil | Métier reconnu | Visibilité | Secteur souhaité |
| --- | --- | --- | --- |
| Hicham Benjelloun | Commercial | VISIBLE | Distribution |
| Imane Tazi | Comptable | VISIBLE | Finance |
| Salma Ouazzani | Comptable | VISIBLE | Finance |
| Karim Idrissi | Développeur logiciel | **HIDDEN** | Informatique |
| Nabil Berrada | Développeur logiciel | VISIBLE | Télécommunications |
| Yassine El Amrani | Développeur logiciel | VISIBLE | Informatique |

**Le but de la carte, mesuré** : une recherche sur « Développeur logiciel » remonte **Berrada et
El Amrani**. C'était impossible avant — les profils de démo n'avaient aucun métier reconnu, et le
filtre cherche un rattachement au dictionnaire, pas un intitulé libre.

**Le garde-fou tient** : Karim Idrissi porte le même métier, la même durée, et n'apparaît pas —
masqué, sans consentement. Il a été enrichi comme les autres exprès : un profil pauvre resterait
absent pour de mauvaises raisons et ne prouverait rien.

**Solde de l'entreprise de démonstration : 2 crédits**, son point de départ, malgré le CV
pré-dévoilé — deux offerts, un acheté, un consommé.

**Chargement sur base déjà peuplée** — dictionnaire complet importé (863 concepts), puis fixtures :
le chargement passe. La purge ramène la table à mes trois concepts, sans collision de code.

**Et l'import ultérieur les ADOPTE**, comme le code l'affirme : relancé par-dessus les fixtures, il
rapporte « **860 concepts créés, 3 mis à jour** » et la table retombe sur 863 entrées — un seul
exemplaire de `poste-003`, `poste-008` et `poste-013`.

**Tests** : `phpunit --filter 'Search|Candidate' tests/Functional` → **167 tests, 1182 assertions,
OK** en 41 s. Dont les 3 tests ajoutés par cette carte.

**Contrat** : `api:openapi:export` comparé à `openapi.json` → **inchangé**.

## Livré

- `api/src/DataFixtures/AppFixtures.php` — trois concepts métier du dictionnaire, six profils
  décrits en une table lisible, le réplica Jarvi et les trois rattachements extraits en méthodes, et
  un dévoilement de démonstration compensé par un achat.
- `api/tests/Functional/DemoProfilesSearchTest.php` — 3 tests : le profil trouvé par son métier, le
  métier résolu par le dictionnaire et non par l'intitulé, le profil sans consentement qui reste
  dehors malgré un rattachement identique.
- `api/tests/Functional/CandidateTest.php` — sept compteurs de population, deux valeurs littérales
  (employeur, numéro), et un témoin désormais fabriqué par le test plutôt qu'emprunté aux fixtures.
- `api/tests/Functional/CandidateQualifiedExperienceTest.php` — le profil est vidé de son expérience
  qualifiée avant d'en recevoir une maîtrisée.

## Reste à faire (hors périmètre)

- **Le contenu pré-dévoilé n'est pas visible depuis le compte de recette.** Il appartient à
  « showcase-recruiter-webapp-saas Demo SARL » (`owner@demo.showcase-recruiter-webapp-saas.example`), alors que `entreprise@showcase-recruiter-webapp-saas.example` appartient
  à une **troisième** entreprise créée par `TeamAccountSeeder`, qui s'exécute **après** les
  fixtures. Elles ne peuvent pas viser une entreprise qui n'existe pas encore.
- **Charger le dictionnaire dans le bootstrap.** Sans lui, la recherche par poste ne remonte rien en
  dev pour **aucun** profil, pas seulement les profils de démo. Écarté ici : c'est un autre sujet.
- **Les cas particuliers** que le cadrage avait déjà écartés : le profil presque vide, celui
  actuellement au Maroc.

## Écart au cadrage

**La suite existante ne pouvait pas rester verte sans être touchée**, et la carte ne l'avait pas vu.
Neuf tests affirmaient des totaux exacts sur la population de démo — « 2 candidats visibles » — que
l'ajout de profils rend faux **mécaniquement**. Deux autres empruntaient aux fixtures un témoin que
l'enrichissement détruit : un profil sans poste actuel dérivé, un profil sans expérience qualifiée.
Ils fabriquent désormais le leur, ce qui les rend indépendants du jeu de démo — c'est leur couplage
qui était le défaut, l'enrichissement n'a fait que le révéler.

**Un choix de données a évité d'en toucher huit de plus** : Nabil Berrada souhaite les télécoms et
non l'informatique, si bien que le filtre `sector=informatique` continue de ne désigner que Yassine
— le témoin sur lequel une dizaine de tests lisent le premier résultat.

**Le dévoilement de démonstration porte sur Berrada, pas sur El Amrani** comme envisagé d'abord :
El Amrani est le témoin d'une règle voisine — un profil déjà payé reste lisible après retrait du
consentement — et le dévoiler faisait passer un 404 attendu en 200.

## Définition de terminé

- [x] Les trois profils de démo portent poste actuel, employeur, niveau d'études, plusieurs
      expériences datées, plusieurs formations, langues et compétences.
- [x] Chacun porte un métier reconnu, ses intitulés et ses secteurs d'expérience.
- [x] Deux ou trois profils complets sont ajoutés, sur des métiers variés.
- [x] Une recherche sur le poste d'un profil de démo le remonte — test à l'appui.
- [x] CV, e-mail et téléphone existent sur **les six** ; le compte rendu d'entretien sur **un**
      seul (Imane Tazi), comme avant — un second aurait déplacé le filtre « profil vedette ».
- [x] Au moins un contenu est déjà dévoilé pour l'entreprise de démonstration.
- [x] Le profil sans consentement reste absent de toute recherche.
- [x] Le chargement passe sur base vide **et** sur base déjà peuplée — les deux éprouvés sur des
      bases jetables.
- [x] `api/openapi.json` inchangé. Suite fonctionnelle : **167 tests verts** sur le périmètre
      `Search|Candidate`, celui que ces fixtures nourrissent. La suite **complète** (~7 min) n'a pas
      été jouée — elle appartient au point de contrôle de la PR.
