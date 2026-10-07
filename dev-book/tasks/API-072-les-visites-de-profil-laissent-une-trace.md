# TASK API-072 — les visites de profil laissent une trace

- **Créée le** : 2026-08-18.
- **Session** : `87c642c6-d8b6-4447-954e-5e3e0b6db5c9`.
- **Statut** : ✅ fait le 2026-08-18 — ouvrir la fiche d'un candidat en tant que recruteur laisse
  une trace, une seule par recruteur, candidat et jour civil ; l'administration n'en laisse aucune.
- **Type** : Backend API — écriture d'une trace d'activité.
- **Tests** : phpunit fonctionnel — une visite créée à la première ouverture, aucune à la seconde le
  même jour par le même recruteur, une nouvelle quand la précédente date de la veille, aucune quand
  l'appelant n'est pas un recruteur, et la lecture qui reste servie même si l'écriture échoue.
- **Réf** : **aval `API-073`** (l'exposition) et `ADM-026` (l'écran) · `requirements/brief/initial_brief.md` §4 ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` § ProfileView · entité `ProfileView` (existe déjà).

## Contexte

Le brief demande « l'historique des profils visités (pour préparer nos futurs indicateurs en temps
réel) ». La table qui le porterait existe depuis le début — `ProfileView`, avec le candidat regardé,
l'entreprise, le recruteur et la date — mais **personne ne l'écrit** : la seule création du dépôt est
dans les données de démonstration (`AppFixtures.php:591`).

Deux conséquences aujourd'hui. showcase-recruiter-webapp-saas ne sait pas qui s'intéresse à qui. Et la page d'activité du
candidat, qui affiche « votre profil a été consulté X fois », affiche **toujours zéro** en dehors
des données de démonstration.

Visiter n'est pas dévoiler : un recruteur peut ouvrir trente fiches masquées sans dépenser un seul
crédit. Les dévoilements sont déjà tracés (`RevealedProfile`) ; les visites, non — et c'est
justement l'écart que le brief veut voir.

## Périmètre

- **À chaque fois qu'un recruteur obtient la fiche d'un candidat** (`GET /v1/candidates/{id}`,
  servie par `CandidateItemProvider`), enregistrer une visite : le candidat, l'organisation du
  recruteur, le recruteur.
- **Une seule visite par recruteur, par candidat et par jour civil.** Les réouvertures suivantes
  n'écrivent rien.
- **Rien n'est enregistré** quand l'appelant n'est pas un recruteur.
- L'écriture est **invisible pour l'appelant** : la réponse servie ne change pas.

## Hors périmètre

- **Exposer** cet historique — c'est `API-073`, et l'écran est `ADM-026`.
- **Compter les apparitions dans une liste de résultats** : apparaître n'est pas être regardé, et le
  volume serait d'un autre ordre.
- **Purger** les visites anciennes : la durée de conservation n'est pas tranchée, cf. la réserve
  ci-dessous.
- **Toucher aux dévoilements** (`RevealedProfile`), qui sont déjà tracés et le restent tels quels.
- Toute exploitation statistique : cette tâche produit la matière, pas les indicateurs.

## Décisions de cadrage

### Le serveur enregistre lui-même, l'interface n'a rien à envoyer

L'autre voie était que l'écran prévienne le serveur qu'il vient d'ouvrir un profil. Elle capture le
geste plus finement, mais **chaque nouvel écran doit y penser** — et un écran qui oublie fait un trou
silencieux dans l'historique. Enregistrer au moment où le serveur sert la fiche est impossible à
oublier et vaut pour tout appelant présent ou futur.

Contrepartie assumée : **une lecture écrit désormais en base**, sur une route utilisée par les
recruteurs. D'où la règle ci-dessous sur l'échec d'écriture.

### Une visite par jour, pas une par ouverture

Un recruteur qui revient trois fois sur la même fiche dans l'après-midi ne s'intéresse pas trois fois
au candidat. Compter chaque ouverture gonflerait la table sans rien apprendre, et rendrait le
compteur montré au candidat (« consulté 30 fois ») trompeur alors qu'une seule personne est passée.
Le jour civil est la maille : lisible, suffisante pour un indicateur, bornée en volume.

### Aucune migration

L'entité existe et convient telle quelle. Rien à ajouter en base.

### La durée de conservation reste ouverte — et c'est écrit

Une visite est une donnée personnelle : elle dit qu'un recruteur nommé a regardé une personne nommée
à une date. Le volet réglementaire du projet est ouvert dans `dev-book/todo.md` et n'est pas tranché.
On conserve donc sans limite pour l'instant, **en le signalant**, plutôt que de fixer une durée à la
sauvette dans une tâche technique.

## Règles et cas limites

- **Appelant sans recruteur rattaché** (administrateur, candidat lisant sa propre fiche, appel de
  service) : aucune écriture. La trace exige les trois liens — candidat, organisation, recruteur.
- **L'administration ne compte pas** : elle consulte les profils par sa propre route
  (`/v1/admin/candidates`), qui n'est pas concernée. Un administrateur ne pollue donc pas l'historique.
- **Un échec d'écriture ne doit jamais faire échouer la lecture.** Le recruteur a demandé une fiche :
  il la reçoit. Une trace perdue est un incident mineur, une fiche perdue est une panne.
- **Course entre deux onglets** : le contrôle d'existence puis l'insertion ne sont pas atomiques. Un
  doublon reste possible en cas de clics simultanés — toléré, sans conséquence (une ligne de trop
  dans un fil, un compteur candidat supérieur de un). Verrouiller une lecture coûterait plus que le
  défaut qu'on éviterait.
- **Profil supprimé ou désactivé** : la trace suit le sort de l'entité, comme les autres relations.
- **Performance** : la route sert une fiche déjà lourde ; le contrôle d'existence doit tenir en une
  requête indexée, et ne pas s'ajouter à chaque appel de la liste de recherche.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` § `ProfileView` — la règle « une visite par
  recruteur, par candidat et par jour », et la réserve sur la conservation.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — signaler que `GET /v1/candidates/{id}`
  laisse une trace quand l'appelant est un recruteur.

## Vérification

**Appels réels**, pile de ce worktree, session recruteur ouverte (`owner@demo.showcase-recruiter-webapp-saas.example`) :

```
état de départ                              1 visite en base
ouverture de la fiche                       HTTP 200  →  2 visites
la même fiche, rouverte                     HTTP 200  →  2 visites
la même fiche, rouverte encore              HTTP 200  →  2 visites
un AUTRE recruteur                          HTTP 200  →  3 visites
un administrateur (pas recruteur)           HTTP 403  →  3 visites
le lendemain, le premier recruteur revient  HTTP 200  →  4 visites
```

**La trace posée**, lue en base :

```
created_at            recruteur       entreprise           candidat
2026-08-18 19:34:22   Sara Bennani    showcase-recruiter-webapp-saas Demo SARL   01900000-…-0000000000c1
```

Le « lendemain » a été **provoqué** : les visites du jour reculées d'un jour, puis nouvel appel. Les
deux lignes du même couple recruteur/candidat coexistent, une au 17, une au 18.

**Tests**

```
phpunit tests/Unit/ProfileViewRecorderTest.php        →  2 tests, 3 assertions, vert
phpunit tests/Functional/ProfileViewTest.php          →  5 tests, 37 assertions, vert
phpunit tests/Functional/CandidateTest.php
        + CandidateActivityTest.php (non-régression)  →  35 tests, 309 assertions, vert
phpunit tests/Functional/RevealTest.php (idem)        →  23 tests, 258 assertions, vert
make dev-openapi                                      →  openapi.json INCHANGÉ (aucune surface touchée)
```

## Livré

- `api/src/Domain/Candidate/ProfileViewRecorder.php` **(+)** — le service qui porte les trois
  règles : recruteur obligatoire, une visite par jour, échec d'écriture avalé et journalisé.
- `api/src/State/Provider/CandidateItemProvider.php` **(M)** — deux lignes : la sortie est construite
  **avant** la trace, puis la trace est posée.
- `api/tests/Unit/ProfileViewRecorderTest.php` **(+)** — le cas que l'appel réel ne sait pas
  provoquer : la base tombe pendant l'écriture, la fiche part quand même.
- `api/tests/Functional/ProfileViewTest.php` **(+)** — les cinq cas observables.
- `docs/…/data-model.md` **(M)** et `api-routes.md` **(M)** — la règle du jour civil, l'effet de bord
  sur la lecture, et la réserve de conservation.

## Écart au cadrage

Aucun. La seule décision prise en cours d'implémentation — **construire la réponse avant de poser la
trace** — sert une règle déjà écrite ici (« un échec d'écriture ne doit jamais faire échouer la
lecture ») : un échec de flush ferme le gestionnaire d'entités, et le constructeur de la réponse a
encore des relations à charger.

## Reste à faire (hors périmètre)

- **Variable morte dans le fichier touché** : `$request = $this->requestStack->getCurrentRequest();`
  (`CandidateItemProvider.php`) n'est utilisée nulle part — vestige d'un code retiré, deux lignes
  au-dessus de celles ajoutées ici. **Volontairement laissée** : hors carte.

## Définition de terminé

- [x] Un recruteur qui ouvre la fiche d'un candidat crée une visite.
- [x] La même fiche rouverte le même jour par le même recruteur n'en crée pas d'autre.
- [x] Un autre recruteur de la même entreprise crée bien la sienne.
- [x] Le lendemain, une nouvelle visite est créée.
- [x] Un appelant sans recruteur rattaché n'écrit rien.
- [x] La fiche reste servie à l'identique, échec d'écriture compris.
- [x] Tests fonctionnels phpunit sur les six points ci-dessus.
- [x] `data-model.md` et `api-routes.md` à jour.
