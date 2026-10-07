# TASK API-024 — api : on garde trace des métiers cherchés que le dictionnaire ignore

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-04 — une recherche lancée sur un métier inconnu laisse une trace,
  relancée elle l'incrémente, et `app:vocabulary:missing` la lit. 5 tests, 23 assertions.
- **Type** : Backend API — nouvelle table et nouvelle commande, aucun changement du contrat exposé.
- **Tests** : fonctionnels — une recherche lancée sur un métier inconnu laisse une trace, la même
  relancée l'incrémente au lieu de créer un doublon, et un métier **reconnu** n'en laisse aucune.
  S'y ajoute un test sur la commande de lecture (classement par nombre décroissant).
- **Réf** : `API-023` (l'auto-complétion, où le manque s'est vu) · `ENT-044` (le champ qui annonce au
  recruteur que son mot n'est pas reconnu — même constat, autre bout) · `API-009` (le dictionnaire et
  ses variantes) · tâche 111 (les concepts proches, mécanique distincte et dormante) ·
  `CandidateRepository::…` (la résolution du critère `job`) ·
  [profile-processing.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md)

## Contexte

Le filtre par métier exige une correspondance **exacte** : le terme cherché doit être, mot pour mot,
le libellé canonique d'un concept ou l'un de ses libellés vivants
(`CandidateRepository`, résolution du critère `job`). Un intitulé que le dictionnaire ignore ramène
donc **zéro profil**, quoi qu'il y ait dans le vivier.

C'est le bon comportement — voir `API-023`, *Décisions de cadrage*, où l'assouplissement du filtre a
été examiné puis écarté. Mais il a une conséquence qu'on ne mesure pas : **on ne sait pas ce que le
dictionnaire ne connaît pas.**

Mesuré sur le vivier de développement, en cherchant cinq intitulés au hasard :

| Terme | Au dictionnaire | Profils dont le titre le mentionne |
| --- | --- | --- |
| `scrum master` | ✅ | 13 |
| `product owner` | ✅ | 17 |
| `data scientist` | ✅ | 26 |
| `prompt engineer` | ❌ | 0 |
| `growth` | ❌ | 26 |

La couverture est bonne, et c'est justement ce qui rend le manque difficile à voir : il ne se
manifeste que sur les termes qu'on n'a pas pensé à tester. **Le dictionnaire s'enrichit aujourd'hui à
l'intuition.** Les recruteurs, eux, tapent chaque jour ce qu'ils cherchent vraiment — et cette
information est jetée.

Une réserve honnête sur le chiffre de droite : le titre de profil n'est **pas** un intitulé de poste.
C'est une accroche rédigée librement — 2 928 titres distincts pour 3 041 profils —, et `growth` y
apparaît dans « Driving Brand Growth », « B2B Growth & Sales Strategy ». Ces 26 profils ne sont pas
des « Growth ». Cette colonne dit qu'un mot **circule** dans le vivier, pas qu'il y désigne un
métier. C'est un indice à instruire, jamais une décision.

## Périmètre

- **Une entité et sa table** : le terme cherché, le nombre de fois, la dernière date.
- **L'enregistrement au lancement d'une recherche**, quand le critère `job` ne résout **aucun**
  concept. Le lancement, côté produit, **crée une recherche enregistrée** — cf. *Écart au cadrage*,
  ce n'est pas l'endpoint qu'on croit.
- **Une commande de lecture**, `app:vocabulary:missing`, qui classe par fréquence décroissante.
- **La ligne de cette commande** dans le tableau de `profile-processing.md`, où vivent déjà
  `app:vocabulary:similarity` et `app:vocabulary:related`.

## Hors périmètre

- **Toute décision automatique.** Rien n'est ajouté au dictionnaire par cette tâche : elle produit
  une liste à instruire, pas un enrichissement.
- **Les mots-clés.** Ils ne résolvent pas contre le dictionnaire par conception (`API-009`) : un
  mot-clé inconnu n'est pas un défaut, c'est le fonctionnement normal du champ.
- **Les termes qui résolvent vers un concept que personne n'exerce.** Ils ramènent zéro eux aussi,
  mais ils disent autre chose — « ce métier existe, le vivier ne l'a pas » — et les mélanger rendrait
  la liste inexploitable. Mesuré : 7 concepts sur 158, tous des pseudo-métiers de statut
  (`Étudiant`, `Bénévolat`, `Stage / Internship`…).
- **Une purge ou une durée de rétention.** La table ne porte aucune donnée personnelle et croît
  lentement ; y ajouter une politique maintenant serait décider sans chiffre.
- **Un écran d'administration.** La commande suffit tant que la liste se lit une fois par mois.
- **L'assouplissement du filtre**, écarté dans `API-023` et qui le reste.

## Décisions de cadrage

### On enregistre au LANCEMENT, pas à la frappe

Enregistrer chaque frappe qui ne propose rien noierait le signal : chaque mot complet est précédé de
tous ses débuts, et « d », « dé », « dév » n'apprennent rien sur ce qui manque.

Le lancement d'une recherche est un **acte d'intention** : le recruteur a tapé, il a lu que ça ne
ramènerait rien, et il a lancé quand même. C'est exactement le signal qu'on veut — quelqu'un
cherchait ce métier-là.

### On garde le mot, pas celui qui l'a tapé

Le terme, son nombre d'occurrences, sa dernière date. Ni recruteur, ni organisation.

Savoir qu'un terme vient de dix entreprises plutôt que d'un recruteur acharné changerait la priorité,
c'est vrai. Mais on constituerait alors un **historique nominatif de ce que chaque client cherche**,
pour un gain de tri. Le nombre d'occurrences suffit à décider quoi instruire en premier ; si un jour
il ne suffit plus, la question se reposera avec un cas réel sous les yeux.

### Le compteur monte en base, pas en PHP

`INSERT … ON CONFLICT (term) DO UPDATE SET occurrences = occurrences + 1`. Deux recherches
simultanées sur le même terme inconnu ne doivent ni créer deux lignes, ni s'écraser l'une l'autre —
et un « lire puis écrire » en PHP fait les deux.

### La clé est le terme normalisé, la trace garde ce qui a été tapé

`Développeur Fullstack` et `développeur fullstack` sont la même demande : ils partagent une clé,
minuscules et espaces de bord retirés. Mais c'est la **casse d'origine** qu'on affiche, parce qu'une
liste de termes à instruire se lit mieux telle qu'elle a été écrite.

### Une commande, parce qu'une table que personne ne sait lire ne sert à rien

La famille existe déjà — `app:vocabulary:similarity`, `app:vocabulary:related` — et se passe à la
main, hors du pipeline quotidien. `app:vocabulary:missing` s'y range naturellement.

Sans elle, l'information demanderait un accès SQL à la production pour être consultée, ce qui revient
à ne jamais la consulter.

## Règles et cas limites

- **Le schéma vient de l'entité.** Le projet n'a aucune migration : la table naît de `make dev-schema`
  et se retrouve en preprod par `preprod-reset`. En **production**, la mise à jour du schéma est un
  geste manuel qui doit être nommé dans la carte à la clôture.
- **L'enregistrement ne doit jamais faire échouer une recherche.** Une écriture de trace qui tombe se
  perd en silence : c'est une observation, pas un résultat. Le recruteur ne doit rien en voir.
- **Une recherche enregistrée qu'on rejoue** compte comme un lancement : c'est bien quelqu'un qui
  cherche ce métier-là, aujourd'hui encore.
- **Le terme vide ou trop court ne laisse pas de trace** : il n'y a pas de métier à en déduire.
- **La longueur est bornée** à l'écriture — un champ de recherche accepte n'importe quoi, et une
  colonne de trace ne doit pas devenir un dépôt de texte libre.
- **Rien n'est enregistré quand le terme résout**, même si la recherche ramène zéro profil pour une
  autre raison (expérience, niveau d'études). Ce qu'on trace, c'est un mot incompris, pas une
  recherche infructueuse.

## Documentation impactée

- **`profile-processing.md`** — le tableau des commandes de vocabulaire gagne
  `app:vocabulary:missing`, avec la même colonne « dans le pipeline : non ».
- **`data-model.md`** — une table apparaît ; elle n'a aucune relation, mais l'inventaire doit rester
  vrai.
- **Vérifié, non impacté** : `api/openapi.json` (aucune route, aucun champ exposé),
  `api-routes.md` (rien de nouveau à appeler), `api-conventions.md`.

## Définition de terminé

- [x] Une recherche lancée sur un métier que le dictionnaire ignore laisse **une** trace.
- [x] La même recherche relancée porte le compteur à 2, sans créer de seconde ligne — et la casse ne
      fait pas deux demandes différentes.
- [x] Une recherche sur un métier **connu** ne laisse aucune trace, même si elle ramène zéro profil ;
      une **variante vivante** non plus.
- [x] `app:vocabulary:missing` liste les termes du plus cherché au moins cherché.
- [x] Une écriture de trace qui échoue ne fait pas échouer la recherche.
- [x] Le geste à passer en production est écrit ci-dessous.

## Vérification

**`UnresolvedJobTermTest` : 5 tests, 23 assertions, vert.** Ils passent par la vraie route HTTP
(`POST /v1/searches`, celle que le formulaire appelle en lançant) et relisent la table :

| Ce qui est éprouvé | Attendu |
| --- | --- |
| Un intitulé inconnu, lancé trois fois (dont une en majuscules) | une ligne, compteur à 3 |
| Un métier **au dictionnaire que personne n'exerce** | aucune trace |
| Une **variante vivante** d'un métier connu | aucune trace |
| Une recherche sans critère de métier | aucune trace |
| `app:vocabulary:missing` | le plus cherché s'affiche avant le plus rare |

**Les 68 tests du domaine recherche passent** (550 assertions) : `SavedSearchProcessor` a gagné un
appel, il n'a rien perdu.

**La table est en place**, avec l'index unique sur `term` sans lequel `ON CONFLICT` ne peut pas
fonctionner :

```
uniq_10e9a3d6a50fe78d  UNIQUE, btree (term)
```

**La commande tourne**, sur une base vierge :

```
$ php bin/console app:vocabulary:missing
 [OK] Aucun intitulé cherché n’est resté sans réponse.
```

**`api/openapi.json` régénéré sur cache vidé : identique.** Rien n'est exposé — la table se lit par
la commande, pas par une route.

**À passer en production, une fois :** la mise à jour du schéma, pour créer la table. Le projet n'a
aucune migration ; en dev c'est `make dev-schema`, en preprod `preprod-reset` la rejoue, en prod
c'est un geste manuel de Johan.

## Livré

- `api/src/Entity/UnresolvedJobTerm.php` **(+)** — la table : terme normalisé unique, libellé tel que
  tapé, compteur, dernière date. Aucune donnée personnelle.
- `api/src/Domain/Search/UnresolvedJobTermRecorder.php` **(+)** — résout contre le dictionnaire, et
  n'écrit que si rien ne répond ; incrément en base par `ON CONFLICT` ; échec avalé en silence.
- `api/src/Command/VocabularyMissingCommand.php` **(+)** — `app:vocabulary:missing`, options
  `--limit` et `--min`.
- `api/src/State/Processor/SavedSearchProcessor.php` **(M)** — deux appels : à la création d'une
  recherche et à l'ajustement de ses critères.
- `api/tests/Functional/UnresolvedJobTermTest.php` **(+)** — 5 tests.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` **(M)** — la table et sa raison d'être.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` **(M)** — la commande, dans la famille
  `app:vocabulary:*`.

## Écart au cadrage

### « Au lancement d'une recherche » ne désignait pas l'endroit que je croyais

La carte disait d'enregistrer « au lancement », en opposant ce moment à la frappe. Elle ne disait pas
**où** ce lancement se produit, et le premier réflexe — `GET /v1/search/candidates`, l'endpoint qui
porte le critère `job` — aurait produit exactement le bruit qu'elle voulait éviter.

Vérifié côté front : cet endpoint est ce que le formulaire appelle **à chaque frappe débouncée** pour
afficher « 12 profils correspondent » (`useSearchVolume` → `/api/candidates/count`). Y poser la trace
aurait enregistré « d », « dé », « dév »…

Le lancement réel, côté produit, **crée une recherche enregistrée** — « la recherche existe dès le
premier critère posé, il n'y a pas d'action Enregistrer ». C'est donc `SavedSearchProcessor` qui
appelle, à la création et à l'ajustement des critères. Le doc-block de l'enregistreur porte
l'avertissement, pour que le prochain lecteur ne refasse pas le raisonnement.

### Le rejeu d'une recherche enregistrée ne compte pas

La carte l'annonçait comme un lancement. À l'implémentation, non : rouvrir une recherche n'est pas
taper un terme. Compter chaque réouverture gonflerait le compteur avec des consultations répétées au
lieu d'intentions distinctes, et le terme a déjà été enregistré quand la recherche a été composée.

Ce que le compteur doit mesurer, c'est **combien de fois quelqu'un a formulé cette demande**, pas
combien de fois il a relu la réponse.
