# TASK API-049 — une recherche sans contact depuis quinze jours se ferme

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — la trace d'activité existe et n'est poussée que par un geste
  humain, et une commande quotidienne clôt ce que plus personne ne touche.
- **Type** : Backend API — trace d'activité, commande de clôture, pose de colonne sans migration.
- **Tests** : fonctionnels phpunit — une recherche touchée hier reste ouverte, une sans contact se
  ferme, une déjà close est ignorée, la seconde passe ne trouve rien ; ouvrir le jeu, trancher un
  profil et rouvrir repoussent la trace ; et surtout **un recalcul de jeu ne la repousse pas**.
- **Réf** : aval `API-050` (le plan de travail, dont cette tâche est la borne réelle) ·
  `api/src/Domain/Search/SearchMembershipRecalculator.php` (la raison pour laquelle `updated_at` ne
  peut pas servir) · `api/src/Command/` (les commandes existantes et leur forme).

## Contexte

**Les recherches s'accumulent sans jamais se ranger.** Un seul compte de la base en porte
**55 ouvertes** — l'écran les empile, et rien ne distingue celle qu'on travaille de celle qu'on a
lancée il y a trois semaines et jamais rouverte.

La clôture existe déjà, mais **à la main seulement** : `closed_at` sur la recherche, geste explicite
du recruteur. Personne ne le pose.

Cette accumulation devient bloquante pour la suite : le plan de travail préchargé (`API-050`) ne tient
que si le nombre de recherches ouvertes est petit. **La clôture automatique est la borne** de ce
préchargement — décision prise au cadrage, contre un plafond arbitraire posé dans la route.

## Périmètre

- **Une trace d'activité dédiée sur la recherche** (`last_activity_at`), et son remplissage initial
  depuis `updated_at` — **sans fichier de migration**, cf. décisions.
- **Elle est repoussée par tout contact avec la recherche** : ouvrir son jeu de résultats, marquer vu,
  écarter, restaurer, retenir, libérer, modifier les critères, renommer, partager, exporter.
- **Une commande de console** qui ferme les recherches sans contact depuis la durée configurée, à
  brancher sur le rythme quotidien existant.
- **La durée vit en configuration**, pas en dur — quinze jours par défaut.
- **La réouverture reste le geste existant** (`PATCH isClosed: false`) et repousse la trace.

## Hors périmètre

- **Prévenir le recruteur** qu'une recherche va se fermer ou vient de se fermer : sujet à part, il
  touche les notifications et leur canal.
- **Supprimer** quoi que ce soit : une recherche close garde son jeu, ses retenus et ses écartés.
- **L'affichage côté entreprise** : les recherches closes descendent déjà sous un pli dans la
  collection, rien à changer.
- **Le recalcul du jeu d'une recherche close** : hors sujet ici, il continue comme aujourd'hui.

## Décisions de cadrage

### Une colonne dédiée, et surtout pas `updated_at`

`recalculateAll()` repasse sur **toutes** les recherches à chaque import Jarvi. Si la trace
d'activité était `updated_at`, chaque import rajeunirait la base entière et **aucune recherche ne se
fermerait jamais**. La trace doit donc être posée par les gestes humains, et par eux seuls.

### Tout contact compte, ouvrir suffit

Une recherche qu'on rouvre est une recherche vivante, même sans y trancher quoi que ce soit. La règle
la plus intuitive est aussi la plus sûre : personne ne doit voir se fermer une recherche qu'il
consultait la veille.

### Hypothèse assumée — la trace est portée par la recherche, pas par le lecteur

Sur une recherche partagée, le geste d'un collègue la garde donc ouverte pour tout le monde. C'est
cohérent avec le fait que `closed_at` est un état de la recherche et non de son lecteur. **Ce point
n'a pas été explicitement tranché au cadrage** : s'il devait l'être autrement, il faudrait une trace
par lecteur, ce qui changerait la nature de la tâche.

### Aucun fichier de migration — décidé à l'implémentation

La carte annonçait « sa migration ». **Le dépôt n'en a aucune** : `dev-schema` recrée le schéma
depuis le mapping Doctrine, et le Makefile l'assume — « migrations différées à la prod ». Écrire la
première ici aurait mis en service un mécanisme que rien n'utilise, pour une colonne.

Le **remplissage reste dû**, lui, et il n'est pas optionnel : PostgreSQL refuse une colonne non nulle
sur une table qui porte des lignes — constaté avant de le corriger, sur les 92 recherches de la base
de développement. Les trois instructions sont donc écrites dans `data-model.md`, pour que la première
migration de production les reprenne telles quelles.

### Un aperçu avant d'agir — décidé à l'implémentation

`--dry-run` ne figurait pas au cadrage. Sur une base qui n'a jamais rangé, la première passe peut
fermer beaucoup d'un coup : pouvoir le regarder avant coûte une méthode et évite une surprise
irréversible à l'échelle d'un compte.

### Quinze jours, en configuration

Le nombre sortira du code dès le premier ajustement produit. Il vit donc au même endroit que les
autres réglages de service.

## Règles et cas limites

- **Une recherche déjà close** est ignorée par la commande : elle ne se referme pas, et sa date de
  clôture ne bouge pas.
- **La réouverture repart pour quinze jours** : rouvrir sans toucher à rien ne doit pas laisser la
  recherche se refermer à la passe suivante.
- **Une recherche sans critère** — elle naît vide et ne matérialise rien — suit la même règle que les
  autres : sans contact, elle se ferme.
- **Horodatage UTC**, comme le reste du modèle ; la comparaison se fait en jours pleins.
- **Idempotence** : la commande relancée deux fois le même jour ne change rien la seconde fois.
- **Volume** : la commande passe sur toutes les recherches ouvertes de la base ; elle doit fermer par
  lots et ne pas charger la table entière en mémoire.
- **Recherche partagée** : elle se ferme comme les autres — le partage ne la protège pas, l'activité
  si.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` — la colonne, son sens, pourquoi elle ne se
  confond pas avec `updated_at`, et **les trois instructions de pose sur une base existante**.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` — un tableau des traitements périodiques,
  qui n'existait pas : la commande, son rythme, son réglage et son `--dry-run`.
- `api/openapi.json` — **régénéré, aucun diff** : cette tâche ne touche aucune surface d'API.

## Vérification

**La commande, aux deux seuils** — le nombre de jours vient de la configuration :

```
seuil par défaut (15 j), --dry-run     Sans contact depuis 15 jours : 0 recherche(s)
SEARCH_INACTIVITY_DAYS=10, --dry-run   5 recherches listées, aucune fermée
```

**Clôture réelle, puis seconde passe** :

```
avant   87 ouvertes · 5 closes
close-inactive (seuil 12 j)   [OK] 3 recherche(s) close(s)
                              * 019fc29d-… — Finance France vers Casablanca
                              * 019fc316-… — (sans nom)
                              * 019fc3a3-… — (sans nom)
après   84 ouvertes · 8 closes
seconde passe                 [OK] 0 recherche(s) close(s)
```

**Le contact repousse la trace**, deux gestes de nature différente :

```
ouvrir le jeu       2026-08-06 18:33  →  GET /results (200)    →  2026-08-15 14:28
trancher un profil  trace remise au 1er juillet  →  POST /reviews (201)  →  2026-08-15 14:29
```

**Et la preuve que les deux dates ne se confondent pas** : les recherches que la commande vient de
fermer portent `updated_at = 2026-08-15 14:27` — la ligne a bien changé — et `last_activity_at`
inchangé au 2026-08-02. Fermer une recherche n'est pas un contact humain.

**Tests joués**

```
phpunit SearchInactivityClosureTest                                OK (7 tests, 35 assertions)
phpunit Search{Closure,,ProfileReview,Export,FavoritesScope}Test    OK (41 tests, 368 assertions)
```

## Livré

- `api/src/Entity/SavedSearch.php` — la colonne `lastActivityAt` et son `touch()`.
- `api/src/Domain/Search/SearchActivityRecorder.php` — le point d'entrée unique des gestes qui
  gardent une recherche vivante.
- `api/src/Domain/Search/InactiveSearchCloser.php` — la clôture par lots, et `pending()` pour
  regarder sans agir.
- `api/src/Command/SearchCloseInactiveCommand.php` — la commande, avec `--dry-run`.
- Six points de contact branchés : ouverture du jeu (`SearchResultsProvider`), revue
  (`ProfileReviewProcessor`), favoris (`FavoriteProcessor`), création / renommage / critères /
  partage / clôture / réouverture (`SavedSearchProcessor`), export (`SearchExportHandler`).
- `api/config/services.yaml` — `SEARCH_INACTIVITY_DAYS`, quinze jours par défaut.
- `api/tests/Functional/SearchInactivityClosureTest.php` — sept cas, dont celui qui protège toute la
  tâche : **un recalcul de jeu n'est pas un contact**.

## Reste à faire (hors périmètre)

- **Le branchement effectif sur le rythme quotidien** (Scheduler / cron du VPS) : la commande est
  prête et documentée, sa planification relève de l'infrastructure.
- **Prévenir le recruteur** qu'une recherche s'est fermée : écarté au cadrage, il faudrait un canal
  de notification.
- **La première migration de production** portera les trois instructions de pose ; elles sont
  écrites dans `data-model.md` en attendant.

## Définition de terminé

- [x] La colonne existe et les recherches existantes sont initialisées — **par SQL et non par une
      migration**, le dépôt n'en ayant pas ; les instructions sont documentées.
- [x] Chaque geste listé au périmètre repousse la trace d'activité.
- [x] Un recalcul de jeu ne repousse **aucune** trace — un test le retient.
- [x] La commande ferme les recherches sans contact depuis la durée configurée, et elles seules.
- [x] Une recherche déjà close est laissée intacte ; une recherche rouverte repart pour la durée
      pleine.
- [x] La durée est configurable et vaut quinze jours par défaut.
- [x] Tests fonctionnels phpunit sur les cinq points ci-dessus.
- [x] Les deux pages de documentation sont à jour.
