# TASK API-028 — api : une recherche se clôture, et se rouvre

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — une recherche se clôture et se rouvre par son auteur ; close,
  elle refuse toute autre écriture et cesse d'annoncer des nouveautés. 7 tests, 60 assertions.
- **Type** : Backend API — un état s'ajoute au modèle et au contrat, et il verrouille l'écriture.
- **Tests** : fonctionnels sur le cycle complet — l'auteur clôture et la date est posée ; un membre
  lisant une recherche partagée reçoit `403` ; modifier une recherche close est refusé en `409` ;
  rouvrir efface la date et rend l'édition ; `newCount` vaut zéro sur une recherche close. Un
  changement d'état assorti de règles de permission ne se prouve que de bout en bout.
- **Réf** : **`ENT-051` — aval** (bouton de clôture, menu d'actions, pli des closes, et mise à jour
  de [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md))
  · **`API-027` — à committer AVANT**, cf. *Décisions de cadrage* · `SavedSearch` ·
  `SavedSearchInput` · `SavedSearchOutput` · `SavedSearchProcessor` · `SavedSearchVoter` ·
  `SearchNewCountReader` ·
  [api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md)

## Contexte

Une recherche ne peut pas être déclarée **terminée**. Elle reste indéfiniment dans la collection, au
même rang que celles en cours : rien ne distingue celle qu'on a menée à son terme de celle qu'on n'a
jamais ouverte.

Le seul geste de sortie existant est la **suppression**, et il ne convient pas : il efface la
recherche, ses favoris et sa trace d'export. Or une recherche aboutie est précisément ce qu'on veut
**garder** — c'est un dossier clos, pas une erreur.

Conséquence, elle continue aussi de réclamer : `newCount` annonce chaque semaine les profils qui ont
rejoint son jeu, alors que plus personne ne les attend.

Le modèle porte déjà un champ mort dans cette famille — `alertEnabled`, jamais lu ni exposé. Cette
tâche n'y touche pas : elle ajoute un état qui, lui, engage quelque chose.

## Périmètre

- `SavedSearch` gagne `closedAt` (`?DateTimeImmutable`, nul par défaut).
- `SavedSearchInput` gagne `isClosed` (`?bool`), à côté d'`isShared`.
- `SavedSearchOutput` gagne `closedAt`, **lu sur l'entité par le mapper** — cf. *Écart* : c'est un
  champ, pas un calcul, aucun appelant n'a à le fournir.
- `SavedSearchProcessor` : pose la date quand `isClosed` vaut `true`, l'efface quand il vaut
  `false`, et **refuse toute autre modification** tant que la recherche est close.
- `newCount` est **forcé à zéro** sur une recherche close, côté serveur.
- Schéma régénéré (`make dev-schema`) et contrat régénéré (`make dev-openapi`) — tous deux du
  contenu de commit, pas des gates.

## Hors périmètre

- **Les deux comptages** `seenCount` et `favoriteCount` : c'est `API-027`.
- **L'écran** — bouton de clôture, menu d'actions, pli qui range les closes : c'est `ENT-051`.
- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**,
  qui énumère « créer · reprendre · dupliquer · partager · supprimer » sans la clôture : page de
  l'espace **consommateur**, et fichier actuellement modifié non commité dans le dossier de travail
  entreprise par une autre session. Elle part avec `ENT-051`.
- **L'enregistrement de qui clôture** : cf. *Décisions de cadrage*.
- **Toute clôture automatique ou suggérée** — « exportée depuis deux mois, la clôturer ? ». Une
  suggestion qui se trompe agace plus qu'un rangement manuel ne fatigue.
- **Un filtre serveur actives / closes.** L'accueil trie et découpe déjà côté écran, avec le motif
  écrit dans son code : *« le tri se fait ici et non côté API : c'est une règle d'affichage de CET
  écran »*. La collection continue de tout servir.
- **`alertEnabled`**, qui dort dans l'entité et continuera d'y dormir.

## Décisions de cadrage

**Un booléen sur le `PATCH` existant, pas d'opération dédiée.** `SavedSearchInput` a tous ses champs
nullables pour permettre le patch partiel, et `isShared` y fait déjà exactement le chemin qu'`isClosed`
doit faire : même opération, même processeur, même voter. Deux points d'entrée `close` et `reopen`
auraient nommé le geste dans le contrat et évité un recalcul, mais au prix de deux opérations et
d'un processeur de plus, pour un booléen. **Coût assumé** : le `PATCH` renvoie
`SavedSearchWithResultsOutput`, donc clôturer recalcule et renvoie le jeu de résultats. Inutile, mais
sans conséquence — et c'était le prix de la simplicité.

**Close verrouille l'écriture, en `409`.** Sans ça, l'état ne veut rien dire : une recherche
« terminée » continuerait de voir ses critères bouger, et son bilan — tant de profils vus, tant de
retenus — cesserait d'être un bilan. La règle vit au **serveur** et pas dans l'écran : une règle
tenue par l'interface seule est contournée par le premier autre client, sans même le savoir.

**`newCount` vaut zéro sur une recherche close.** C'est une **exception assumée** à la posture
d'`API-027`, où le serveur rend le fait brut et laisse l'écran décider. La raison de l'écart :
`newCount` ne décrit pas un état neutre, il dit *« il y a quelque chose à aller voir »*. Sur une
recherche close, c'est faux. Laisser la règle aux clients, c'est garantir qu'un client l'oubliera, et
qu'une recherche terminée se remettra à clignoter chaque semaine.

**On n'enregistre pas qui a clôturé.** `SavedSearchVoter` réserve `WRITE` à l'auteur — *« l'auteur,
et lui seul »*. Une colonne « clôturée par » ne pourrait donc **jamais** contenir autre chose que le
propriétaire, déjà servi par `ownerId` et `ownerName`. L'écran compose « close le 12 mars par
Karim B. » à partir de ce qu'il a déjà. La colonne ne deviendrait utile que le jour où un tiers
pourrait clôturer — un administrateur, un traitement automatique —, et rien de tel n'est prévu.

**`409` et pas `422`.** Le dépôt réserve `422` à une charge utile qui n'a pas de sens et refuse un
conflit d'**état** en `409` — précédent documenté dans
[api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md) (« déjà rattaché
à une entreprise »). Modifier une recherche close est un conflit d'état : la charge utile est
parfaitement valide, c'est la recherche qui ne l'accepte plus.

**`API-027` se commite AVANT que celle-ci commence.** Les deux tâches modifient `SavedSearchOutput`
et `CollaborationMapper::toSavedSearch`. Menées de front, leurs diffs se mélangeraient dans les mêmes
fichiers, et cet environnement ne permet pas de découper l'index (`git add -p` indisponible). Ce
n'est pas une préférence d'ordonnancement, c'est une contrainte d'outillage.

## Règles et cas limites

- **Rouvrir** efface `closedAt`. C'est la **seule** écriture qu'une recherche close accepte, et elle
  reste toujours ouverte à son auteur.
- **Clôturer une recherche déjà close** ne change rien et répond `200` : c'est l'affirmation d'un
  état, pas une transition. **Hypothèse assumée** — aucun besoin exprimé ne réclame de distinguer les
  deux, et un `409` ici punirait un double clic.
- **Un membre lisant une recherche partagée** qui tente de la clôturer reçoit `403`, par le voter
  existant. Aucune règle nouvelle : lire n'a jamais autorisé à écrire.
- **Une recherche close reste lisible** et continue de figurer dans la collection servie par
  `GET /v1/searches`. C'est l'écran qui la range, pas le serveur qui la cache.
- **`resultCount` et les comptages d'`API-027` continuent d'être servis** sur une recherche close :
  ils forment son bilan, et c'est ce qu'on vient y lire.
- **Une recherche sans critère** se clôture comme une autre. Le serveur n'a pas à juger si le travail
  méritait d'être mené.
- **`closedAt` est nul** sur toute recherche existante : aucune reprise de données, l'état par défaut
  est « active ».

## Écart au cadrage

### Le mapper n'a pas eu besoin d'un paramètre de plus

La carte annonçait que `closedAt` serait « fourni par les quatre appels à
`CollaborationMapper::toSavedSearch` ». C'était calquer la clôture sur les comptages d'`API-027`, à
tort : un comptage se calcule ailleurs et doit donc être passé, alors que la date de clôture est un
**champ de l'entité**, comme `isShared`. Le mapper la lit directement, et **aucun des quatre
appelants ne bouge d'une ligne**.

Bénéfice inattendu : la règle qui éteint `newCount` sur une recherche close tient elle aussi dans ce
seul endroit, au lieu d'être répétée quatre fois.

### `closedAt` n'est pas servi à `null` — il est ABSENT

API Platform ne sérialise pas les valeurs nulles, et ce n'est pas propre à ce champ : c'est le
comportement de **toute l'API**. Une recherche active n'a donc simplement pas la clé.

Conséquence pour le front : le type généré sera `closedAt?: string`, et un client lira `undefined`
plutôt que `null`. Ça ne change rien à un test de vérité — `search.closedAt ? close : active` — mais
la carte disait « redevient nul », ce qui n'est pas ce qu'on observe. Le critère est reformulé.

### Une recherche naît toujours active

Rien dans le cadrage ne disait quoi faire d'un `isClosed` posé sur la **création**. Il est **ignoré**
en silence : aucun besoin ne réclame de créer une recherche déjà terminée, et l'autoriser ouvrirait
un état sans usage. **Règle assumée**, couverte par un test — pas un refus explicite, qui aurait
demandé un code d'erreur pour un cas que personne ne provoque.

## Documentation impactée

- `api/openapi.json` — régénéré par `make dev-openapi`. La CI `api-quality` le vérifie par un
  `git diff --exit-code` que la commande locale ne couvre pas : régénérer **avant** de committer,
  sinon vert en local et rouge en CI.

Aucune page de `docs/showcase-recruiter-webapp-saas/` n'est rendue fausse **par ce diff** : celle qui l'est —
`arborescence-entreprise.md`, qui énumère les gestes de l'écran — décrit l'espace entreprise et part
avec `ENT-051`, d'autant qu'une autre session la modifie en ce moment. Aucun `README.md` de dossier
n'existe sous `api/`.

## Définition de terminé

- [x] L'auteur clôture sa recherche par `PATCH /v1/searches/{id}` et la réponse porte `closedAt`.
- [x] Il la rouvre par le même appel, et `closedAt` **disparaît de la réponse** — cf. *Écart* : une
      valeur nulle n'est pas sérialisée, sur ce champ comme sur tous les autres.
- [x] Modifier les critères, le nom ou le partage d'une recherche close est refusé en `409`.
- [x] Un membre d'une recherche partagée qui tente de la clôturer reçoit `403`.
- [x] `newCount` vaut `0` sur une recherche close, y compris quand des profils ont rejoint son jeu.
- [x] Clôturer une recherche déjà close répond `200` sans rien changer.
- [x] `closedAt` est porté par le DTO commun, donc servi par les quatre chemins qui rendent une
      recherche — sans qu'aucun ait eu à le fournir, cf. *Écart*.
- [x] Schéma et `api/openapi.json` régénérés et à jour dans le commit.
- [x] Les champs existants, le jeu de recherches rendu et son ordre sont inchangés.

## Vérification

**Le cycle complet, en requêtes réelles sur la stack de la lane :**

| Geste | Réponse | État lu ensuite |
| --- | --- | --- |
| à la création | — | `closedAt` absent, active |
| repère de lecture reculé | — | `newCount` = **2** |
| clôture | **200** | `closedAt` = 16:37:20, `newCount` = **0** |
| modifier les critères | **409** | — |
| renommer | **409** | — |
| partager | **409** | — |
| re-clôturer | **200** | date **inchangée** |
| rouvrir | **200** | `closedAt` absent |
| renommer, rouverte | **200** | nom appliqué |

**L'extinction de la nouveauté n'est pas un effet de bord.** Chaque écriture remet aussi le repère de
lecture, ce qui suffirait à expliquer le zéro. Isolé par un aller-retour à repère **strictement
identique**, vieilli après coup :

| État | `newCount` |
| --- | --- |
| close | **0** |
| rouverte, même repère | **2** |

**Permissions**, sur la recherche partagée des fixtures : le membre **lit** (200), tente de
**clôturer** (**403**), l'auteur clôture (200), le membre **voit la date**, puis **duplique** (201).

**`SearchClosureTest` : 7 tests, 60 assertions, vert.** Cycle complet · refus en 409 sur les trois
champs · idempotence de la date · 403 du membre qui voit quand même l'état · extinction de la
nouveauté prouvée par l'aller-retour · suppression d'une close toujours permise · naissance toujours
active.

**Les 43 tests des recherches passent** (407 assertions), `SearchCountsTest` d'`API-027` compris.

**Schéma** : `ALTER TABLE saved_search ADD closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL` —
une colonne, aucune donnée touchée.

**`api/openapi.json` régénéré : +19 lignes** — `closedAt` sur le schéma `Search`, `isClosed` sur les
deux schémas d'entrée.

**Piège rencontré trois fois** : le cache de métadonnées d'API Platform sert un contrat sans les
nouveaux champs alors que l'entité les porte — tests rouges sur des `null`, et une régénération de
contrat identique à l'existant. `rm -rf var/cache/*` dans le conteneur le règle. C'est le même piège
qui donne vert en local et rouge en CI.

## Livré

- `api/src/Entity/SavedSearch.php` **(M)** — `closedAt`, **hors du constructeur** : une recherche ne
  naît jamais close, et l'y faire figurer inviterait à la créer dans cet état.
- `api/src/ApiResource/Input/SavedSearchInput.php` **(M)** — `isClosed`, à côté d'`isShared`.
- `api/src/ApiResource/Output/SavedSearchOutput.php` **(M)** — `closedAt`, et ce qu'il engage.
- `api/src/State/CollaborationMapper.php` **(M)** — lit la date sur l'entité, et **éteint
  `newCount`** en un seul endroit pour les quatre chemins.
- `api/src/State/Processor/SavedSearchProcessor.php` **(M)** — pose et efface la date ; refuse en
  409 toute autre écriture sur une close ; la suppression, elle, reste permise.
- `api/tests/Functional/SearchClosureTest.php` **(+)** — 7 tests.
- `api/openapi.json` **(M)** — `closedAt` et `isClosed`.

## Reste à faire (hors périmètre)

- **`ENT-051`** : le bouton de clôture, le menu d'actions, le pli qui range les closes, et la mise à
  jour d'`arborescence-entreprise.md`, qui énumère les gestes d'une recherche sans la clôture.
- **Aucun filtre serveur actives / closes** n'a été ajouté : le tri et le découpage restent des
  règles d'écran, comme l'accueil le fait déjà.
- **`alertEnabled` dort toujours** dans l'entité, jamais lu ni exposé.
